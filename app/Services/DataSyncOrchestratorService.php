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
        $target   = storage_path('app/public/nas/' . $fileName);

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
            $this->rsyncService->syncCrawlerFileToNas(
                $sourcePath,
                $target
            );

            Log::info('File sync orchestration successful', [
                'item_id'     => $item->id,
                'target_path' => $target,
            ]);

            // 3. Update sync record status
            $record->update([
                'status'      => 'completed',
                'finished_at' => now(),
            ]);

            // 4. Update the original CrawlerTaskItem status and public URL
            $publicUrl = \Illuminate\Support\Facades\Storage::url('nas/' . $fileName);
            $item->update([
                'status'      => 'synced',
                'result_file' => $publicUrl,
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

        $fullPath = storage_path("app/public/nas/{$fileName}");

        Log::info('Saving to path', [
            'path' => $fullPath,
        ]);

        $response = Http::timeout(300)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("Download failed: {$url}");
        }

        $written = file_put_contents($fullPath, $response->body());

        if ($written === false) {
            throw new \RuntimeException("Failed to write file: {$fullPath}");
        }

        if (! file_exists($fullPath)) {
            throw new \RuntimeException("File does not exist after write");
        }

        if (filesize($fullPath) === 0) {
            throw new \RuntimeException("File is empty");
        }

        $publicUrl = asset("storage/nas/{$fileName}");

        Log::info('Download success', [
            'path' => $fullPath,
            'url'  => $publicUrl,
        ]);

        $item->update([
            'status'      => 'synced',
            'result_file' => $publicUrl,
        ]);

        $this->aiTaskManagerService->createFromCrawlerItem($item);

        $item->task()->update([
            'status' => 'completed',
        ]);

        return $fullPath;
    }

    public function syncCaseScreenshotToNasWithHttp(\App\Models\CaseManagementItem $item): string
    {
        $url = $item->media_url;

        Log::info('Screenshot HTTP download started', [
            'item_id' => $item->id,
            'url'     => $url,
        ]);

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException("Invalid URL: {$url}");
        }

        $fileName = basename(parse_url($url, PHP_URL_PATH));

        $fullPath = storage_path("app/public/nas/{$fileName}");

        Log::info('Saving screenshot to path', [
            'path' => $fullPath,
        ]);

        $response = Http::timeout(300)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("Download failed: {$url}");
        }

        $written = file_put_contents($fullPath, $response->body());

        if ($written === false) {
            throw new \RuntimeException("Failed to write file: {$fullPath}");
        }

        if (! file_exists($fullPath)) {
            throw new \RuntimeException("File does not exist after write");
        }

        if (filesize($fullPath) === 0) {
            throw new \RuntimeException("File is empty");
        }

        $publicUrl = asset("storage/nas/{$fileName}");

        Log::info('Screenshot download success', [
            'path' => $fullPath,
            'url'  => $publicUrl,
        ]);

        $item->update([
            'media_url' => $publicUrl,
        ]);

        return $fullPath;
    }
}
