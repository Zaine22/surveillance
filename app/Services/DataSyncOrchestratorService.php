<?php
namespace App\Services;

use App\Models\CrawlerTaskItem;
use App\Models\DataSyncRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

class DataSyncOrchestratorService
{
    public function __construct(
        protected RsyncService $rsyncService,
        protected AiTaskManagerService $aiTaskManagerService
    ) {}

    public function syncCrawlerFileToNas(CrawlerTaskItem $item): string
    {
        $sourcePath = $item->result_file;
        Log::info('started syncing ===>');

        Log::info('Source path', [
            'source_path' => $sourcePath,
        ]);

        // Handle URL-based source paths (e.g., http://45.77.241.149/static/zips/file.zip)
        // if (filter_var($sourcePath, FILTER_VALIDATE_URL)) {
        //     // Get the path after the domain (e.g., /static/zips/file.zip)
        //     $path = parse_url($sourcePath, PHP_URL_PATH);

        //     // Map the web path /static/ to the SFTP jail path /storage/
        //     // Use a regex to only match it at the start of the path
        //     $sourcePath = preg_replace('/^\/static\//', 'storage/', $path);

        //     Log::info('Converted URL to remote filesystem path', [
        //         'original' => $item->result_file,
        //         'mapped' => $sourcePath,
        //     ]);
        // }

        if (filter_var($sourcePath, FILTER_VALIDATE_URL)) {

            $fileName = basename($sourcePath);

            $sourcePath = "zips/{$fileName}";

            Log::info('Converted URL to SFTP path (FIXED)', [
                'original' => $item->result_file,
                'mapped'   => $sourcePath,
            ]);
        }

        $fileName = basename($sourcePath);
        $target   = '/mnt/task/' . $fileName;

        // 1. Create the sync record
        $record = DB::transaction(function () use ($item, $target) {
            return DataSyncRecord::create([
                'id'          => (string) Str::uuid(),
                'source_path' => $item->result_file,
                'target_path' => $target,
                'file_name'   => basename($target),
                'status'      => 'transferring',
                'retry_count' => 0,
                'max_retry'   => 3,
                'started_at'  => now(),
            ]);
        });

        // 2. Perform the sync outside the transaction
        try {
            // comment for the frank server no sync

            // $this->rsyncService->syncCrawlerFileToNas(
            //     $sourcePath,
            //     $target
            // );

            Log::info('File sync orchestration successful', [
                'item_id' => $item->id,
                // 'target_path' => $target, //comment for frank server
            ]);

            // 3. Update sync record status
            $record->update([
                'status'      => 'completed',
                'finished_at' => now(),
            ]);

            // 4. Update the original CrawlerTaskItem status and public URL
            $publicUrl = '/mnt/task/' . $fileName;
            $item->update([
                'status' => 'synced',
                // 'result_file' => $publicUrl, //comment for frank server
            ]);
            $this->aiTaskManagerService->createFromCrawlerItem($item);

            $item->task()->update([
                'status' => 'completed',
            ]);

            return $target;

        } catch (Throwable $e) {
            Log::error('File sync orchestration failed', [
                'item_id' => $item->id,
                'error'   => $e->getMessage(),
            ]);

            // 5. Update sync record status on failure
            $record->update([
                'status'        => 'failed',
                'retry_count'   => $record->retry_count + 1,
                'error_message' => $e->getMessage(),
                'finished_at'   => now(),
            ]);

            // 6. Update the original CrawlerTaskItem to error
            $item->update([
                'status'        => 'error',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }

    }

    // public function syncCrawlerFileToNasWithHttp(CrawlerTaskItem $item): string
    // {
    //     $url = $item->result_file;

    //     Log::info('HTTP download started', [
    //         'item_id' => $item->id,
    //         'url'     => $url,
    //     ]);

    //     if (! filter_var($url, FILTER_VALIDATE_URL)) {
    //         throw new \RuntimeException("Invalid URL: {$url}");
    //     }

    //     $fileName = basename(parse_url($url, PHP_URL_PATH));
    //     $fullPath = '/mnt/task/' . $fileName;

    //     Log::info('Saving to path', [
    //         'path' => $fullPath,
    //     ]);

    //     try {
    //         $response = Http::timeout(300)->get($url);

    //         if (! $response->successful()) {
    //             throw new \RuntimeException("Download failed with status {$response->status()}: {$url}");
    //         }

    //         $written = file_put_contents($fullPath, $response->body());

    //         if ($written === false) {
    //             throw new \RuntimeException("Failed to write file: {$fullPath}");
    //         }

    //         if (! file_exists($fullPath)) {
    //             throw new \RuntimeException("File does not exist after write: {$fullPath}");
    //         }

    //         if (filesize($fullPath) === 0) {
    //             throw new \RuntimeException("File is empty after write: {$fullPath}");
    //         }

    //         Log::info('HTTP download success', [
    //             'path' => $fullPath,
    //             'url'  => $url,
    //             'size' => filesize($fullPath),
    //         ]);

    //         $item->update([
    //             'status'      => 'synced',
    //             'result_file' => $fullPath,
    //         ]);

    //         $this->aiTaskManagerService->createFromCrawlerItem($item);

    //         $item->task()->update([
    //             'status' => 'completed',
    //         ]);

    //         return $fullPath;

    //     } catch (Throwable $e) {
    //         Log::error('HTTP download failed', [
    //             'item_id' => $item->id,
    //             'url'     => $url,
    //             'error'   => $e->getMessage(),
    //         ]);

    //         $item->update([
    //             'status'        => 'error',
    //             'error_message' => $e->getMessage(),
    //         ]);

    //         throw $e;
    //     }
    // }

    public function syncCrawlerFileToNasWithHttp(CrawlerTaskItem $item): string
    {
        $url = $item->result_file;

        Log::info('HTTP download started', [
            'item_id' => $item->id,
            'url'     => $url,
        ]);

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException("Invalid URL: {$url}");
        }

        try {
            $item->loadMissing('task.crawlerConfig');

            $sanitizeFileNamePart = function (?string $value, string $fallback): string {
                $value = trim((string) $value);

                if ($value === '') {
                    $value = $fallback;
                }

                // Remove invalid filename characters
                $value = preg_replace('/[\\\\\/:*?"<>|]/', '_', $value);

                // Replace multiple spaces with single underscore
                $value = preg_replace('/\s+/', '_', $value);

                return $value ?: $fallback;
            };

            $crawlerConfigName = $sanitizeFileNamePart(
                $item->task?->crawlerConfig?->name ?? 'CrawlerConfig',
                'CrawlerConfig'
            );

            $keywords = is_array($item->keywords)
                ? $item->keywords
                : json_decode($item->keywords ?? '[]', true);

            $firstKeyword = $sanitizeFileNamePart(
                $keywords[0] ?? 'Keyword',
                'Keyword'
            );

            $crawlLocationName = $sanitizeFileNamePart(
                $this->getNameFromCrawlLocation($item->crawl_location ?? 'Location'),
                'Location'
            );

            $fileName = "{$crawlerConfigName}_{$firstKeyword}_{$crawlLocationName}.zip";

            $fullPath = '/mnt/task/' . $fileName;

            Log::info('Saving to path', [
                'path'      => $fullPath,
                'file_name' => $fileName,
            ]);

            $response = Http::timeout(300)
                ->sink($fullPath)
                ->get($url);

            if (! $response->successful()) {
                throw new \RuntimeException("Download failed with status {$response->status()}: {$url}");
            }

            if (! file_exists($fullPath)) {
                throw new \RuntimeException("File does not exist after download: {$fullPath}");
            }

            if (filesize($fullPath) === 0) {
                throw new \RuntimeException("File is empty after download: {$fullPath}");
            }

            Log::info('HTTP download success', [
                'path' => $fullPath,
                'url'  => $url,
                'size' => filesize($fullPath),
            ]);

            // Unzip the file if it's a ZIP archive
            if (pathinfo($fullPath, PATHINFO_EXTENSION) === 'zip') {
                $this->unzipFile($fullPath);
            }

            $item->update([
                'status'      => 'synced',
                'result_file' => $fullPath,
            ]);

            $this->aiTaskManagerService->createFromCrawlerItem($item);

            $item->task()->update([
                'status' => 'completed',
            ]);

            return $fullPath;

        } catch (Throwable $e) {
            Log::error('HTTP download failed', [
                'item_id' => $item->id,
                'url'     => $url,
                'error'   => $e->getMessage(),
            ]);

            $item->update([
                'status'        => 'error',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function syncCaseScreenshotToNas(\App\Models\CaseManagementItem $item): string
    {
        $url = $item->media_url;

        Log::info('Screenshot HTTP download started', [
            'item_id' => $item->id,
            'url'     => $url,
        ]);

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException("Invalid screenshot URL: {$url}");
        }

        // The Laravel and Python servers are on the same machine.
        // Replace the external IP with 127.0.0.1 to avoid loopback timeout.
        $parsedHost  = parse_url($url, PHP_URL_HOST);
        $downloadUrl = str_replace($parsedHost, '127.0.0.1', $url);

        $fileName = basename(parse_url($url, PHP_URL_PATH));
        $fullPath = '/mnt/task/' . $fileName;

        Log::info('Saving screenshot to path', [
            'original_url' => $url,
            'download_url' => $downloadUrl,
            'path'         => $fullPath,
        ]);

        $record = DB::transaction(function () use ($item, $fullPath) {
            return DataSyncRecord::create([
                'id'          => (string) Str::uuid(),
                'source_path' => $item->media_url,
                'target_path' => $fullPath,
                'file_name'   => basename($fullPath),
                'status'      => 'transferring',
                'retry_count' => 0,
                'max_retry'   => 3,
                'started_at'  => now(),
            ]);
        });

        try {
            $response = Http::timeout(300)->get($downloadUrl);

            if (! $response->successful()) {
                throw new \RuntimeException("Download failed with status {$response->status()}: {$url}");
            }

            $written = file_put_contents($fullPath, $response->body());

            if ($written === false) {
                throw new \RuntimeException("Failed to write screenshot: {$fullPath}");
            }

            if (! file_exists($fullPath)) {
                throw new \RuntimeException("Screenshot file does not exist after write: {$fullPath}");
            }

            if (filesize($fullPath) === 0) {
                throw new \RuntimeException("Screenshot file is empty after write: {$fullPath}");
            }

            Log::info('Screenshot HTTP download success', [
                'item_id' => $item->id,
                'path'    => $fullPath,
                'size'    => filesize($fullPath),
            ]);

            $record->update([
                'status'      => 'completed',
                'finished_at' => now(),
            ]);

            $publicUrl = config('app.url') . '/api/task/' . $fileName;

            $item->update([
                'media_url' => $publicUrl,
            ]);

            return $publicUrl;

        } catch (Throwable $e) {
            Log::error('Screenshot HTTP download failed', [
                'item_id' => $item->id,
                'url'     => $url,
                'error'   => $e->getMessage(),
            ]);

            $record->update([
                'status'        => 'failed',
                'retry_count'   => $record->retry_count + 1,
                'error_message' => $e->getMessage(),
                'finished_at'   => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Unzip a file to the same directory
     *
     * @param string $zipPath Full path to the ZIP file
     * @return void
     * @throws \RuntimeException
     */
    private function unzipFile(string $zipPath): void
    {
        $zip = new ZipArchive();
        $extractPath = dirname($zipPath);

        Log::info('Starting unzip process', [
            'zip_file' => $zipPath,
            'extract_to' => $extractPath,
        ]);

        $result = $zip->open($zipPath);

        if ($result !== true) {
            $errorMessage = match ($result) {
                ZipArchive::ER_NOZIP => 'Not a valid ZIP archive',
                ZipArchive::ER_INCONS => 'Inconsistent ZIP archive',
                ZipArchive::ER_CRC => 'CRC error',
                ZipArchive::ER_OPEN => 'Cannot open file',
                ZipArchive::ER_READ => 'Read error',
                ZipArchive::ER_SEEK => 'Seek error',
                default => "Unknown error (code: {$result})",
            };

            throw new \RuntimeException("Failed to open ZIP file: {$errorMessage}");
        }

        try {
            if (!$zip->extractTo($extractPath)) {
                throw new \RuntimeException("Failed to extract ZIP file to: {$extractPath}");
            }

            $fileCount = $zip->numFiles;
            $zip->close();

            Log::info('Unzip completed successfully', [
                'zip_file' => $zipPath,
                'extracted_files' => $fileCount,
                'extract_path' => $extractPath,
            ]);

        } catch (Throwable $e) {
            $zip->close();

            Log::error('Unzip failed', [
                'zip_file' => $zipPath,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException("Failed to unzip file: {$e->getMessage()}", 0, $e);
        }
    }

    private function getNameFromCrawlLocation(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 'Location';
        }

        // If URL has no scheme, add temporary scheme for parse_url
        $parseValue = str_contains($value, '://')
            ? $value
            : 'https://' . $value;

        $host = parse_url($parseValue, PHP_URL_HOST);

        if (! $host) {
            $host = $value;
        }

        // Remove www.
        $host = preg_replace('/^www\./', '', $host);

        // Example:
        // https://51cg1.com => 51cg1
        // https://520cc.com => 520cc
        $name = explode('.', $host)[0] ?? 'Location';

        // Sanitize filename part
        $name = preg_replace('/[\\\\\/:*?"<>|]/', '_', $name);
        $name = preg_replace('/\s+/', '_', $name);

        return $name ?: 'Location';
    }
}
