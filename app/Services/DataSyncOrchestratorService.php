<?php
namespace App\Services;

use App\Models\CrawlerTaskItem;
use App\Models\DataSyncRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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
                'item_id'     => $item->id,
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
                'status'      => 'synced',
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

        $fileName = basename(parse_url($url, PHP_URL_PATH));
        $fullPath = '/mnt/task/' . $fileName;

        Log::info('Saving to path', [
            'path' => $fullPath,
        ]);

        try {
            $response = Http::timeout(300)->get($url);

            if (! $response->successful()) {
                throw new \RuntimeException("Download failed with status {$response->status()}: {$url}");
            }

            $written = file_put_contents($fullPath, $response->body());

            if ($written === false) {
                throw new \RuntimeException("Failed to write file: {$fullPath}");
            }

            if (! file_exists($fullPath)) {
                throw new \RuntimeException("File does not exist after write: {$fullPath}");
            }

            if (filesize($fullPath) === 0) {
                throw new \RuntimeException("File is empty after write: {$fullPath}");
            }

            Log::info('HTTP download success', [
                'path' => $fullPath,
                'url'  => $url,
                'size' => filesize($fullPath),
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

            $item->update([
                'status'        => 'error',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }


    public function syncCaseScreenshotToNas(\App\Models\CaseManagementItem $item): string
    {
        $sourcePath = $item->media_url;

        Log::info('Screenshot SFTP download started', [
            'item_id' => $item->id,
            'url'     => $sourcePath,
        ]);

        if (filter_var($sourcePath, FILTER_VALIDATE_URL)) {
            $fileName = basename(parse_url($sourcePath, PHP_URL_PATH));

            // Extract the directory from the URL path, assuming it matches the SFTP directory (e.g. 'screenshots' or 'zips')
            $pathParts = explode('/', trim(parse_url($sourcePath, PHP_URL_PATH), '/'));
            $folder = count($pathParts) >= 2 ? $pathParts[count($pathParts) - 2] : 'screenshots';

            $sourcePath = "{$folder}/{$fileName}";

            Log::info('Converted screenshot URL to SFTP path', [
                'original' => $item->media_url,
                'mapped'   => $sourcePath,
            ]);
        } else {
            $fileName = basename($sourcePath);
        }

        $target = '/mnt/task/' . $fileName;

        $record = DB::transaction(function () use ($item, $target, $sourcePath) {
            return DataSyncRecord::create([
                'id'          => (string) Str::uuid(),
                'source_path' => $item->media_url,
                'target_path' => $target,
                'file_name'   => basename($target),
                'status'      => 'transferring',
                'retry_count' => 0,
                'max_retry'   => 3,
                'started_at'  => now(),
            ]);
        });

        try {
            $this->rsyncService->syncCrawlerFileToNas(
                $sourcePath,
                $target
            );

            Log::info('Screenshot sync orchestration successful', [
                'item_id'     => $item->id,
                'target_path' => $target,
            ]);

            $record->update([
                'status'      => 'completed',
                'finished_at' => now(),
            ]);

            $publicUrl = '/mnt/task/' . $fileName;

            $item->update([
                'media_url' => $publicUrl,
            ]);

            return $target;

        } catch (Throwable $e) {
            Log::error('Screenshot sync orchestration failed', [
                'item_id' => $item->id,
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
}