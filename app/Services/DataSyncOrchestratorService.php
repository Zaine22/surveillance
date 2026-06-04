<?php
namespace App\Services;

use App\Models\CrawlerTaskItem;
use App\Models\DataSyncRecord;
use App\Services\RsyncService;
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
        protected AiTaskManagerService $aiTaskManagerService,
        protected CleanFileServerPollingService $pollingService
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

        // Generate file name first for use in both record and download
        $fileName = $this->generateFileName($item);
        $fullPath = '/mnt/task/' . $fileName;

        // 1. Create the sync record
        $record = DB::transaction(function () use ($item, $fullPath) {
            return DataSyncRecord::create([
                'id'          => (string) Str::uuid(),
                'source_path' => $item->result_file,
                'target_path' => $fullPath,
                'file_name'   => basename($fullPath),
                'status'      => 'transferring',
                'retry_count' => 0,
                'max_retry'   => 3,
                'started_at'  => now(),
            ]);
        });

        try {
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

            // 3. Update sync record status
            $record->update([
                'status'      => 'completed',
                'finished_at' => now(),
            ]);

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

            // 5. Update sync record status on failure
            $record->update([
                'status'        => 'failed',
                'retry_count'   => $record->retry_count + 1,
                'error_message' => $e->getMessage(),
                'finished_at'   => now(),
            ]);

            $item->update([
                'status'        => 'error',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate a sanitized file name for the crawler item
     */
    private function generateFileName(CrawlerTaskItem $item): string
    {
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

        $crawlLocationName = $sanitizeFileNamePart(
            $this->getNameFromCrawlLocation($item->crawl_location ?? 'Location'),
            'Location'
        );

        $fileDate = now()->format('Y_m_d_His');

        return "{$fileDate}_{$crawlLocationName}.zip";
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

        // Get the directory and filename without extension
        $extractPath = dirname($zipPath);
        $zipFileName = basename($zipPath, '.zip');

        // Create a folder with the same name as the zip file (without .zip extension)
        $targetFolder = $extractPath . '/' . $zipFileName;

        Log::info('Starting unzip process', [
            'zip_file'   => $zipPath,
            'extract_to' => $targetFolder,
        ]);

        $result = $zip->open($zipPath);

        if ($result !== true) {
            $errorMessage = match ($result) {
                ZipArchive::ER_NOZIP  => 'Not a valid ZIP archive',
                ZipArchive::ER_INCONS => 'Inconsistent ZIP archive',
                ZipArchive::ER_CRC    => 'CRC error',
                ZipArchive::ER_OPEN   => 'Cannot open file',
                ZipArchive::ER_READ   => 'Read error',
                ZipArchive::ER_SEEK   => 'Seek error',
                default               => "Unknown error (code: {$result})",
            };

            throw new \RuntimeException("Failed to open ZIP file: {$errorMessage}");
        }

        try {
            // Create the target folder if it doesn't exist
            if (! is_dir($targetFolder)) {
                mkdir($targetFolder, 0755, true);
            }

            if (! $zip->extractTo($targetFolder)) {
                throw new \RuntimeException("Failed to extract ZIP file to: {$targetFolder}");
            }

            $fileCount = $zip->numFiles;
            $zip->close();

            Log::info('Unzip completed successfully', [
                'zip_file'        => $zipPath,
                'extracted_files' => $fileCount,
                'extract_path'    => $targetFolder,
            ]);

        } catch (Throwable $e) {
            $zip->close();

            Log::error('Unzip failed', [
                'zip_file' => $zipPath,
                'error'    => $e->getMessage(),
            ]);

            throw new \RuntimeException("Failed to unzip file: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Sync crawler file to NAS using rsync with SSH key authentication
     * This method uses rsync over SSH to transfer files from the clean file server
     */
    public function syncCrawlerFileToNasWithRsync(CrawlerTaskItem $item): string
    {
        $url = $item->result_file;

        Log::info('Rsync download started', [
            'item_id' => $item->id,
            'url'     => $url,
        ]);

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException("Invalid URL: {$url}");
        }

        // Generate file name first for use in both record and download
        $fileName = $this->generateFileName($item);
        $fullPath = '/mnt/task/' . $fileName;

        // Extract the remote path from the URL
        // Example: http://35.194.240.94/static/zips/file.zip -> /var/www/html/static/zips/file.zip
        $remotePath = $this->convertUrlToRemotePath($url);

        // 1. Create the sync record
        $record = DB::transaction(function () use ($item, $fullPath) {
            return DataSyncRecord::create([
                'id'          => (string) Str::uuid(),
                'source_path' => $item->result_file,
                'target_path' => $fullPath,
                'file_name'   => basename($fullPath),
                'status'      => 'transferring',
                'retry_count' => 0,
                'max_retry'   => 3,
                'started_at'  => now(),
            ]);
        });

        try {
            Log::info('Syncing via rsync', [
                'remote_path' => $remotePath,
                'local_path'  => $fullPath,
                'file_name'   => $fileName,
            ]);

            // Use the RsyncService to sync from clean file server
            $this->rsyncService->syncFromCleanFileServer($remotePath, $fullPath);

            if (! file_exists($fullPath)) {
                throw new \RuntimeException("File does not exist after rsync: {$fullPath}");
            }

            if (filesize($fullPath) === 0) {
                throw new \RuntimeException("File is empty after rsync: {$fullPath}");
            }

            Log::info('Rsync download success', [
                'path' => $fullPath,
                'url'  => $url,
                'size' => filesize($fullPath),
            ]);

            // Unzip the file if it's a ZIP archive
            if (pathinfo($fullPath, PATHINFO_EXTENSION) === 'zip') {
                $this->unzipFile($fullPath);
            }

            // 3. Update sync record status
            $record->update([
                'status'      => 'completed',
                'finished_at' => now(),
            ]);

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
            Log::error('Rsync download failed', [
                'item_id' => $item->id,
                'url'     => $url,
                'error'   => $e->getMessage(),
            ]);

            // 5. Update sync record status on failure
            $record->update([
                'status'        => 'failed',
                'retry_count'   => $record->retry_count + 1,
                'error_message' => $e->getMessage(),
                'finished_at'   => now(),
            ]);

            $item->update([
                'status'        => 'error',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Convert a URL to a remote file system path
     * Example: http://35.194.240.94/static/zips/file.zip -> /var/www/html/static/zips/file.zip
     */
    private function convertUrlToRemotePath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        // Remove leading slash and prepend the web root path
        // Adjust this path based on your server's actual web root
        $webRoot = '/var/www/html';

        return $webRoot . $path;
    }

    /**
     * Complete sync flow: FileServer → CleanFileServer → MainWeb
     * This method handles the complete two-hop transfer:
     * 1. Transfer from unscanned file server (34.81.79.232) to clean file server (35.194.240.94)
     * 2. Transfer from clean file server (35.194.240.94) to main web server (220.130.187.241)
     */
    public function syncUnscannedFileToMainWeb(CrawlerTaskItem $item, ?DataSyncRecord $existingRecord = null): string
    {
        $url = $item->result_file;

        // If it's not a URL, construct it from the filename
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $baseUrl = 'http://34.81.79.232/home/rsyncbot/unscann-files';
            $url = $baseUrl . '/' . $url;
        }

        Log::info('Complete file sync started (FileServer → CleanFileServer → MainWeb)', [
            'item_id' => $item->id,
            'url'     => $url,
            'is_retry' => $existingRecord !== null,
        ]);

        // Use existing target path for retries, generate new one for first attempt
        if ($existingRecord) {
            $mainWebPath = $existingRecord->target_path;
            $fileName = basename($mainWebPath);
        } else {
            $fileName = $this->generateFileName($item);
            $mainWebPath = '/mnt/task/' . $fileName;
        }

        // Extract the source path from the URL
        // Example: http://34.81.79.232/home/rsyncbot/unscann-files/file.zip -> /home/rsyncbot/unscann-files/file.zip
        $sourcePath = parse_url($url, PHP_URL_PATH);

        // Target path on the clean file server
        $cleanServerPath = '/home/rsyncbot/clean-files/' . basename($sourcePath);

        // 1. Use existing record or create new one
        if ($existingRecord) {
            $record = $existingRecord;
            $record->update([
                'status' => 'transferring',
                'started_at' => now(),
            ]);
        } else {
            $record = DB::transaction(function () use ($item, $mainWebPath) {
                return DataSyncRecord::create([
                    'id'          => (string) Str::uuid(),
                    'source_path' => $item->result_file,
                    'target_path' => $mainWebPath,
                    'file_name'   => basename($mainWebPath),
                    'status'      => 'transferring',
                    'retry_count' => 0,
                    'max_retry'   => 3,
                    'started_at'  => now(),
                ]);
            });
        }

        try {
            // Step 1: Transfer from FileServer to CleanFileServer
            Log::info('Step 1: Syncing from FileServer to CleanFileServer', [
                'source_path' => $sourcePath,
                'target_path' => $cleanServerPath,
            ]);

            $this->rsyncService->syncFromUnscannedToCleanFileServer($sourcePath, $cleanServerPath);

            Log::info('Step 1 completed: File transferred to CleanFileServer', [
                'clean_server_path' => $cleanServerPath,
            ]);

            // Step 2: Transfer from CleanFileServer to MainWeb
            Log::info('Step 2: Syncing from CleanFileServer to MainWeb', [
                'source_path' => $cleanServerPath,
                'target_path' => $mainWebPath,
            ]);

            $this->rsyncService->syncFromCleanFileServer($cleanServerPath, $mainWebPath);

            if (! file_exists($mainWebPath)) {
                throw new \RuntimeException("File does not exist after rsync: {$mainWebPath}");
            }

            if (filesize($mainWebPath) === 0) {
                throw new \RuntimeException("File is empty after rsync: {$mainWebPath}");
            }

            Log::info('Step 2 completed: File transferred to MainWeb', [
                'main_web_path' => $mainWebPath,
                'size' => filesize($mainWebPath),
            ]);

            // Unzip the file if it's a ZIP archive
            if (pathinfo($mainWebPath, PATHINFO_EXTENSION) === 'zip') {
                $this->unzipFile($mainWebPath);
            }

            // 3. Update sync record status
            $record->update([
                'status'      => 'completed',
                'finished_at' => now(),
            ]);

            $item->update([
                'status'      => 'synced',
                'result_file' => $mainWebPath,
            ]);

            $this->aiTaskManagerService->createFromCrawlerItem($item);

            $item->task()->update([
                'status' => 'completed',
            ]);

            Log::info('Complete file sync successful', [
                'item_id' => $item->id,
                'final_path' => $mainWebPath,
            ]);

            return $mainWebPath;

        } catch (Throwable $e) {
            Log::error('Complete file sync failed', [
                'item_id' => $item->id,
                'url'     => $url,
                'error'   => $e->getMessage(),
            ]);

            // 5. Update sync record status on failure
            $record->update([
                'status'        => 'failed',
                'retry_count'   => $record->retry_count + 1,
                'error_message' => $e->getMessage(),
                'finished_at'   => now(),
            ]);

            $item->update([
                'status'        => 'error',
                'error_message' => $e->getMessage(),
            ]);

            // 6. Auto-schedule retry if not exceeded max retries (only for new records, not retries)
            if (!$existingRecord && $record->retry_count < $record->max_retry) {
                // Exponential backoff: 5 min, 15 min, 45 min
                $delayMinutes = 5 * pow(3, $record->retry_count);

                Log::info('Auto-scheduling retry for failed sync', [
                    'record_id' => $record->id,
                    'retry_count' => $record->retry_count,
                    'max_retry' => $record->max_retry,
                    'delay_minutes' => $delayMinutes,
                    'next_retry_at' => now()->addMinutes($delayMinutes),
                ]);

                \App\Jobs\RetryFailedSyncJob::dispatch($record)
                    ->delay(now()->addMinutes($delayMinutes));
            } elseif ($existingRecord) {
                Log::info('Retry failed, will be handled by RetryFailedSyncJob', [
                    'record_id' => $record->id,
                    'retry_count' => $record->retry_count,
                ]);
            } else {
                Log::warning('Max retries exceeded, not scheduling retry', [
                    'record_id' => $record->id,
                    'retry_count' => $record->retry_count,
                    'max_retry' => $record->max_retry,
                ]);
            }

            throw $e;
        }
    }

    /**
     * Sync unscanned file from first server to clean file server using rsync.
     * This method handles files from the unscanned file server (34.81.79.232)
     * and transfers them to the clean file server (35.194.240.94) via private IP (192.168.0.10).
     */
    public function syncUnscannedFileToCleanServer(CrawlerTaskItem $item): string
    {
        $url = $item->result_file;

        Log::info('Unscanned file sync started', [
            'item_id' => $item->id,
            'url'     => $url,
        ]);

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException("Invalid URL: {$url}");
        }

        // Generate file name
        $fileName = $this->generateFileName($item);

        // Extract the source path from the URL
        // Example: http://34.81.79.232/home/rsyncbot/unscann-files/file.zip -> /home/rsyncbot/unscann-files/file.zip
        $sourcePath = parse_url($url, PHP_URL_PATH);

        // Target path on the clean file server
        $targetPath = '/home/rsyncbot/clean-files/' . basename($sourcePath);

        // 1. Create the sync record
        $record = DB::transaction(function () use ($item, $targetPath) {
            return DataSyncRecord::create([
                'id'          => (string) Str::uuid(),
                'source_path' => $item->result_file,
                'target_path' => $targetPath,
                'file_name'   => basename($targetPath),
                'status'      => 'transferring',
                'retry_count' => 0,
                'max_retry'   => 3,
                'started_at'  => now(),
            ]);
        });

        try {
            Log::info('Syncing unscanned file via rsync', [
                'source_path' => $sourcePath,
                'target_path' => $targetPath,
                'file_name'   => $fileName,
            ]);

            // Use the RsyncService to sync from unscanned server to clean server
            $this->rsyncService->syncFromUnscannedToCleanFileServer($sourcePath, $targetPath);

            Log::info('Unscanned file sync success', [
                'source_path' => $sourcePath,
                'target_path' => $targetPath,
            ]);

            // 3. Update sync record status
            $record->update([
                'status'      => 'completed',
                'finished_at' => now(),
            ]);

            // Build the public URL for the clean file server
            $publicUrl = 'http://35.194.240.94' . $targetPath;

            $item->update([
                'status'      => 'synced',
                'result_file' => $publicUrl,
            ]);

            $this->aiTaskManagerService->createFromCrawlerItem($item);

            $item->task()->update([
                'status' => 'completed',
            ]);

            return $targetPath;

        } catch (Throwable $e) {
            Log::error('Unscanned file sync failed', [
                'item_id' => $item->id,
                'url'     => $url,
                'error'   => $e->getMessage(),
            ]);

            // 5. Update sync record status on failure
            $record->update([
                'status'        => 'failed',
                'retry_count'   => $record->retry_count + 1,
                'error_message' => $e->getMessage(),
                'finished_at'   => now(),
            ]);

            $item->update([
                'status'        => 'error',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync file from CleanFileServer to MainWeb with polling
     * This method waits for the file to appear on CleanFileServer (synced by cron)
     * then downloads it to MainWeb
     */
    public function syncFromCleanFileServerWithPolling(CrawlerTaskItem $item, ?DataSyncRecord $existingRecord = null): string
    {
        $url = $item->result_file;

        // If it's not a URL, construct it from the filename
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $baseUrl = 'http://34.81.79.232/home/rsyncbot/unscann-files';
            $url = $baseUrl . '/' . $url;
        }

        Log::info('Polling sync started (wait for CleanFileServer → download to MainWeb)', [
            'item_id' => $item->id,
            'url'     => $url,
            'is_retry' => $existingRecord !== null,
        ]);

        // Use existing target path for retries, generate new one for first attempt
        if ($existingRecord) {
            $mainWebPath = $existingRecord->target_path;
            $fileName = basename($mainWebPath);
        } else {
            $fileName = $this->generateFileName($item);
            $mainWebPath = '/mnt/task/' . $fileName;
        }

        // Get the filename from the URL
        $sourceFileName = basename(parse_url($url, PHP_URL_PATH));
        $cleanServerPath = '/home/rsyncbot/clean-files/' . $sourceFileName;

        // 1. Use existing record or create new one
        if ($existingRecord) {
            $record = $existingRecord;
            $record->update([
                'status' => 'waiting',
                'started_at' => now(),
            ]);
        } else {
            $record = DB::transaction(function () use ($item, $mainWebPath) {
                return DataSyncRecord::create([
                    'id'          => (string) Str::uuid(),
                    'source_path' => $item->result_file,
                    'target_path' => $mainWebPath,
                    'file_name'   => basename($mainWebPath),
                    'status'      => 'waiting',
                    'retry_count' => 0,
                    'max_retry'   => 3,
                    'started_at'  => now(),
                ]);
            });
        }

        try {
            // Step 1: Wait for file to appear on CleanFileServer (cron will sync it)
            Log::info('Step 1: Waiting for file on CleanFileServer (cron sync)', [
                'source_filename' => $sourceFileName,
                'clean_server_path' => $cleanServerPath,
            ]);

            $fileExists = $this->pollingService->waitForFile($sourceFileName, 600, 30);

            if (!$fileExists) {
                throw new \RuntimeException("Timeout waiting for file on CleanFileServer: {$sourceFileName}");
            }

            Log::info('Step 1 completed: File found on CleanFileServer', [
                'clean_server_path' => $cleanServerPath,
            ]);

            // Step 2: Download from CleanFileServer to MainWeb
            $record->update(['status' => 'transferring']);

            Log::info('Step 2: Downloading from CleanFileServer to MainWeb', [
                'source_path' => $cleanServerPath,
                'target_path' => $mainWebPath,
            ]);

            $this->rsyncService->syncFromCleanFileServer($cleanServerPath, $mainWebPath);

            if (! file_exists($mainWebPath)) {
                throw new \RuntimeException("File does not exist after rsync: {$mainWebPath}");
            }

            if (filesize($mainWebPath) === 0) {
                throw new \RuntimeException("File is empty after rsync: {$mainWebPath}");
            }

            Log::info('Step 2 completed: File downloaded to MainWeb', [
                'main_web_path' => $mainWebPath,
                'size' => filesize($mainWebPath),
            ]);

            // Unzip the file if it's a ZIP archive
            if (pathinfo($mainWebPath, PATHINFO_EXTENSION) === 'zip') {
                $this->unzipFile($mainWebPath);
            }

            // 3. Update sync record status
            $record->update([
                'status'      => 'completed',
                'finished_at' => now(),
            ]);

            $item->update([
                'status'      => 'synced',
                'result_file' => $mainWebPath,
            ]);

            $this->aiTaskManagerService->createFromCrawlerItem($item);

            $item->task()->update([
                'status' => 'completed',
            ]);

            Log::info('Polling sync successful', [
                'item_id' => $item->id,
                'final_path' => $mainWebPath,
            ]);

            return $mainWebPath;

        } catch (Throwable $e) {
            Log::error('Polling sync failed', [
                'item_id' => $item->id,
                'url'     => $url,
                'error'   => $e->getMessage(),
            ]);

            // 5. Update sync record status on failure
            $record->update([
                'status'        => 'failed',
                'retry_count'   => $record->retry_count + 1,
                'error_message' => $e->getMessage(),
                'finished_at'   => now(),
            ]);

            $item->update([
                'status'        => 'error',
                'error_message' => $e->getMessage(),
            ]);

            // 6. Auto-schedule retry if not exceeded max retries (only for new records, not retries)
            if (!$existingRecord && $record->retry_count < $record->max_retry) {
                // Exponential backoff: 5 min, 15 min, 45 min
                $delayMinutes = 5 * pow(3, $record->retry_count);

                Log::info('Auto-scheduling retry for failed polling sync', [
                    'record_id' => $record->id,
                    'retry_count' => $record->retry_count,
                    'max_retry' => $record->max_retry,
                    'delay_minutes' => $delayMinutes,
                    'next_retry_at' => now()->addMinutes($delayMinutes),
                ]);

                \App\Jobs\RetryFailedSyncJob::dispatch($record)
                    ->delay(now()->addMinutes($delayMinutes));
            } elseif ($existingRecord) {
                Log::info('Retry failed, will be handled by RetryFailedSyncJob', [
                    'record_id' => $record->id,
                    'retry_count' => $record->retry_count,
                ]);
            } else {
                Log::warning('Max retries exceeded, not scheduling retry', [
                    'record_id' => $record->id,
                    'retry_count' => $record->retry_count,
                    'max_retry' => $record->max_retry,
                ]);
            }

            throw $e;
        }
    }

    /**
     * Sync file from CleanFileServer to MainWeb with polling and encryption
     * This method waits for the file to appear on CleanFileServer (synced by cron),
     * downloads it to tmpzip/, unzips, encrypts, and saves to /mnt/task/
     */
    public function syncFromCleanFileServerWithPollingAndEncryption(CrawlerTaskItem $item, ?DataSyncRecord $existingRecord = null): string
    {
        $url = $item->result_file;

        // If it's not a URL, construct it from the filename
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $baseUrl = 'http://34.81.79.232/home/rsyncbot/unscann-files';
            $url = $baseUrl . '/' . $url;
        }

        Log::info('Polling sync with encryption started', [
            'item_id' => $item->id,
            'url'     => $url,
            'is_retry' => $existingRecord !== null,
        ]);

        // Use existing target path for retries, generate new one for first attempt
        if ($existingRecord) {
            $finalZipPath = $existingRecord->target_path;
            $fileName = basename($finalZipPath);
        } else {
            $fileName = $this->generateFileName($item);
            $finalZipPath = '/mnt/task/' . $fileName;
        }

        // Tmp paths - use configured tmpzip path
        $tmpBasePath = config('app.tmp_zip_path', '/mnt/tmpzip');
        $tmpZipPath = $tmpBasePath . '/' . $fileName;
        $tmpFolderPath = $tmpBasePath . '/' . pathinfo($fileName, PATHINFO_FILENAME);

        // Get the filename from the URL
        $sourceFileName = basename(parse_url($url, PHP_URL_PATH));
        $cleanServerPath = '/home/rsyncbot/clean-files/' . $sourceFileName;

        // 1. Use existing record or create new one
        if ($existingRecord) {
            $record = $existingRecord;
            $record->update([
                'status' => 'waiting',
                'started_at' => now(),
            ]);
        } else {
            $record = DB::transaction(function () use ($item, $finalZipPath) {
                return DataSyncRecord::create([
                    'id'          => (string) Str::uuid(),
                    'source_path' => $item->result_file,
                    'target_path' => $finalZipPath,
                    'file_name'   => basename($finalZipPath),
                    'status'      => 'waiting',
                    'retry_count' => 0,
                    'max_retry'   => 3,
                    'started_at'  => now(),
                ]);
            });
        }

        try {
            // Step 1: Wait for file to appear on CleanFileServer (cron will sync it)
            Log::info('Step 1: Waiting for file on CleanFileServer (cron sync)', [
                'source_filename' => $sourceFileName,
                'clean_server_path' => $cleanServerPath,
            ]);

            $fileExists = $this->pollingService->waitForFile($sourceFileName, 600, 30);

            if (!$fileExists) {
                throw new \RuntimeException("Timeout waiting for file on CleanFileServer: {$sourceFileName}");
            }

            Log::info('Step 1 completed: File found on CleanFileServer', [
                'clean_server_path' => $cleanServerPath,
            ]);

            // Step 2: Download from CleanFileServer to /tmp/
            $record->update(['status' => 'transferring']);

            Log::info('Step 2: Downloading from CleanFileServer to /tmp/', [
                'source_path' => $cleanServerPath,
                'tmp_path' => $tmpZipPath,
            ]);

            $this->rsyncService->syncFromCleanFileServer($cleanServerPath, $tmpZipPath);

            if (! file_exists($tmpZipPath)) {
                throw new \RuntimeException("File does not exist after rsync: {$tmpZipPath}");
            }

            if (filesize($tmpZipPath) === 0) {
                throw new \RuntimeException("File is empty after rsync: {$tmpZipPath}");
            }

            Log::info('Step 2 completed: File downloaded to /tmp/', [
                'tmp_path' => $tmpZipPath,
                'size' => filesize($tmpZipPath),
            ]);

            // Step 3: Unzip in /tmp/ directory
            if (pathinfo($tmpZipPath, PATHINFO_EXTENSION) === 'zip') {
                $this->unzipFile($tmpZipPath);
            }

            Log::info('Step 3 completed: Unzipped to tmp folder', [
                'tmp_folder' => $tmpFolderPath,
            ]);

            // Step 4: Create two-layer encrypted zip and save to /mnt/task/
            $encryptionResult = $this->createTwoLayerEncryptedZip($tmpFolderPath, $finalZipPath);
            $encryptedZipPath = $encryptionResult['zip_path'];
            $dynamicKey = $encryptionResult['dynamic_key'];

            Log::info('Step 4 completed: Two-layer encrypted zip created', [
                'source_folder' => $tmpFolderPath,
                'encrypted_zip' => $encryptedZipPath,
                'dynamic_key' => $dynamicKey,
            ]);

            // Step 5: Schedule cleanup job for 30 days later
            $retentionDays = config('app.tmp_file_retention_days', 30);
            $cleanupDate = now()->addDays($retentionDays);

            \App\Jobs\CleanupTmpFilesJob::dispatch(
                pathinfo($fileName, PATHINFO_FILENAME),
                $cleanupDate
            )->delay($cleanupDate);

            Log::info('Step 5 completed: Scheduled tmp cleanup', [
                'folder_name' => pathinfo($fileName, PATHINFO_FILENAME),
                'cleanup_date' => $cleanupDate,
            ]);

            // 3. Update sync record status
            $record->update([
                'status'      => 'completed',
                'finished_at' => now(),
            ]);

            $item->update([
                'status'      => 'synced',
                'result_file' => $encryptedZipPath,
                'dynamic_key' => $dynamicKey,
            ]);

            $this->aiTaskManagerService->createFromCrawlerItem($item);

            $item->task()->update([
                'status' => 'completed',
            ]);

            Log::info('Polling sync with encryption successful', [
                'item_id' => $item->id,
                'final_path' => $encryptedZipPath,
            ]);

            return $encryptedZipPath;

        } catch (Throwable $e) {
            Log::error('Polling sync with encryption failed', [
                'item_id' => $item->id,
                'url'     => $url,
                'error'   => $e->getMessage(),
            ]);

            // Clean up tmp files on error
            if (isset($tmpZipPath) && file_exists($tmpZipPath)) {
                @unlink($tmpZipPath);
            }
            if (isset($tmpFolderPath) && is_dir($tmpFolderPath)) {
                $this->recursiveRemoveDirectory($tmpFolderPath);
            }

            // 5. Update sync record status on failure
            $record->update([
                'status'        => 'failed',
                'retry_count'   => $record->retry_count + 1,
                'error_message' => $e->getMessage(),
                'finished_at'   => now(),
            ]);

            $item->update([
                'status'        => 'error',
                'error_message' => $e->getMessage(),
            ]);

            // 6. Auto-schedule retry if not exceeded max retries (only for new records, not retries)
            if (!$existingRecord && $record->retry_count < $record->max_retry) {
                // Exponential backoff: 5 min, 15 min, 45 min
                $delayMinutes = 5 * pow(3, $record->retry_count);

                Log::info('Auto-scheduling retry for failed polling sync with encryption', [
                    'record_id' => $record->id,
                    'retry_count' => $record->retry_count,
                    'max_retry' => $record->max_retry,
                    'delay_minutes' => $delayMinutes,
                    'next_retry_at' => now()->addMinutes($delayMinutes),
                ]);

                \App\Jobs\RetryFailedSyncJob::dispatch($record)
                    ->delay(now()->addMinutes($delayMinutes));
            } elseif ($existingRecord) {
                Log::info('Retry failed, will be handled by RetryFailedSyncJob', [
                    'record_id' => $record->id,
                    'retry_count' => $record->retry_count,
                ]);
            } else {
                Log::warning('Max retries exceeded, not scheduling retry', [
                    'record_id' => $record->id,
                    'retry_count' => $record->retry_count,
                    'max_retry' => $record->max_retry,
                ]);
            }

            throw $e;
        }
    }

    /**
     * Create a two-layer encrypted zip file (dynamic key + static key)
     *
     * @param string $sourceFolderPath Path to the folder to zip
     * @param string $finalZipPath Path where the final encrypted zip should be saved
     * @return array ['zip_path' => string, 'dynamic_key' => string, 'static_key' => string]
     * @throws \RuntimeException
     */
    private function createTwoLayerEncryptedZip(string $sourceFolderPath, string $finalZipPath): array
    {
        // Static key (from config)
        $staticKey = config('app.zip_encryption_password', 'surveillance123@#');

        // Dynamic key (generated per file)
        $dynamicKey = $this->generateDynamicKey($finalZipPath);

        Log::info('Creating two-layer encrypted zip', [
            'source_folder' => $sourceFolderPath,
            'final_zip' => $finalZipPath,
            'dynamic_key' => $dynamicKey,
        ]);

        // Layer 1: Encrypt with dynamic key
        $tempZip = sys_get_temp_dir() . '/' . uniqid('layer1_') . '.zip';
        $this->createEncryptedZipWithPassword($sourceFolderPath, $tempZip, $dynamicKey);

        Log::info('Layer 1 encryption completed', [
            'temp_zip' => $tempZip,
            'key_type' => 'dynamic',
        ]);

        // Layer 2: Encrypt with static key
        // Use {dir_path}_encrypted.zip naming for clarity
        $dirPath = pathinfo($finalZipPath, PATHINFO_FILENAME);
        $tempFolder = sys_get_temp_dir() . '/' . uniqid('layer2_');
        mkdir($tempFolder, 0755, true);
        rename($tempZip, $tempFolder . '/' . $dirPath . '_encrypted.zip');

        $this->createEncryptedZipWithPassword($tempFolder, $finalZipPath, $staticKey);

        Log::info('Layer 2 encryption completed', [
            'final_zip' => $finalZipPath,
            'key_type' => 'static',
            'inner_name' => $dirPath . '_encrypted.zip',
        ]);

        // Cleanup
        unlink($tempFolder . '/' . $dirPath . '_encrypted.zip');
        rmdir($tempFolder);

        return [
            'zip_path' => $finalZipPath,
            'dynamic_key' => $dynamicKey,
            'static_key' => $staticKey,
        ];
    }

    /**
     * Generate a dynamic encryption key based on filename and timestamp
     *
     * @param string $filePath Path to the file
     * @return string Generated dynamic key
     */
    private function generateDynamicKey(string $filePath): string
    {
        $fileName = basename($filePath);
        $timestamp = now()->timestamp;
        $salt = config('app.encryption_salt', 'surveillance_salt_2024');

        // Generate unique key: hash(filename + timestamp + salt)
        return hash('sha256', $fileName . $timestamp . $salt);
    }

    /**
     * Create an encrypted (password-protected) zip file from a folder with specific password
     *
     * @param string $sourceFolderPath Path to the folder to zip
     * @param string $targetZipPath Path where the encrypted zip should be saved
     * @param string $password Password to use for encryption
     * @return string Path to the created encrypted zip file
     * @throws \RuntimeException
     */
    private function createEncryptedZipWithPassword(string $sourceFolderPath, string $targetZipPath, string $password): string
    {
        $zip = new ZipArchive();

        Log::info('Creating encrypted zip with custom password', [
            'source_folder' => $sourceFolderPath,
            'target_zip' => $targetZipPath,
        ]);

        // Ensure target directory exists
        $targetDir = dirname($targetZipPath);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $result = $zip->open($targetZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== true) {
            throw new \RuntimeException("Failed to create ZIP file: {$targetZipPath}");
        }

        try {
            // Set password for the archive
            $zip->setPassword($password);

            // Recursively add files from the folder
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceFolderPath),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            $fileCount = 0;
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($sourceFolderPath) + 1);

                    // Add file to zip
                    $zip->addFile($filePath, $relativePath);

                    // Encrypt this file with AES-256
                    $zip->setEncryptionName($relativePath, ZipArchive::EM_AES_256);

                    $fileCount++;
                }
            }

            $zip->close();

            if (!file_exists($targetZipPath)) {
                throw new \RuntimeException("Encrypted ZIP file was not created: {$targetZipPath}");
            }

            Log::info('Encrypted zip created successfully', [
                'zip_file' => $targetZipPath,
                'file_count' => $fileCount,
                'size' => filesize($targetZipPath),
            ]);

            return $targetZipPath;

        } catch (Throwable $e) {
            $zip->close();

            // Clean up partial zip file
            if (file_exists($targetZipPath)) {
                @unlink($targetZipPath);
            }

            Log::error('Failed to create encrypted zip', [
                'source_folder' => $sourceFolderPath,
                'target_zip' => $targetZipPath,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException("Failed to create encrypted zip: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Create an encrypted (password-protected) zip file from a folder
     *
     * @param string $sourceFolderPath Path to the folder to zip
     * @param string $targetZipPath Path where the encrypted zip should be saved
     * @return string Path to the created encrypted zip file
     * @throws \RuntimeException
     */
    private function createEncryptedZip(string $sourceFolderPath, string $targetZipPath): string
    {
        $zip = new ZipArchive();

        // Get password from config
        $password = config('app.zip_encryption_password', 'default_secure_password_2024');

        Log::info('Creating encrypted zip', [
            'source_folder' => $sourceFolderPath,
            'target_zip' => $targetZipPath,
        ]);

        // Ensure target directory exists
        $targetDir = dirname($targetZipPath);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $result = $zip->open($targetZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== true) {
            throw new \RuntimeException("Failed to create ZIP file: {$targetZipPath}");
        }

        try {
            // Set password for the archive
            $zip->setPassword($password);

            // Recursively add files from the folder
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceFolderPath),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            $fileCount = 0;
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($sourceFolderPath) + 1);

                    // Add file to zip
                    $zip->addFile($filePath, $relativePath);

                    // Encrypt this file with AES-256
                    $zip->setEncryptionName($relativePath, ZipArchive::EM_AES_256);

                    $fileCount++;
                }
            }

            $zip->close();

            if (!file_exists($targetZipPath)) {
                throw new \RuntimeException("Encrypted ZIP file was not created: {$targetZipPath}");
            }

            Log::info('Encrypted zip created successfully', [
                'zip_file' => $targetZipPath,
                'file_count' => $fileCount,
                'size' => filesize($targetZipPath),
            ]);

            return $targetZipPath;

        } catch (Throwable $e) {
            $zip->close();

            // Clean up partial zip file
            if (file_exists($targetZipPath)) {
                @unlink($targetZipPath);
            }

            Log::error('Failed to create encrypted zip', [
                'source_folder' => $sourceFolderPath,
                'target_zip' => $targetZipPath,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException("Failed to create encrypted zip: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Recursively remove a directory and all its contents
     *
     * @param string $dir Directory path to remove
     * @return void
     */
    private function recursiveRemoveDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->recursiveRemoveDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
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