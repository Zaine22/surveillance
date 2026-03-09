<?php

namespace App\Services;

use App\Models\CrawlerTaskItem;
use App\Models\DataSyncRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DataSyncOrchestratorService
{
    public function __construct(
        protected RsyncService $rsyncService
    ) {}

    public function syncCrawlerFileToNas(CrawlerTaskItem $item): string
    {
        $sourcePath = $item->result_file;
        Log::info('started syncing ===>');

        Log::info('Source path', [
            'source_path' => $sourcePath,
        ]);

        // Handle URL-based source paths (e.g., http://45.77.241.149/static/zips/file.zip)
        if (filter_var($sourcePath, FILTER_VALIDATE_URL)) {
            // Get the path after the domain (e.g., /static/zips/file.zip)
            $path = parse_url($sourcePath, PHP_URL_PATH);

            // Map the web path /static/ to the SFTP jail path /storage/
            // Use a regex to only match it at the start of the path
            $sourcePath = preg_replace('/^\/static\//', 'storage/', $path);

            Log::info('Converted URL to remote filesystem path', [
                'original' => $item->result_file,
                'mapped' => $sourcePath,
            ]);
        }

        $fileName = basename($sourcePath);
        $target = storage_path('app/public/nas/'.$fileName);

        // 1. Create the sync record
        $record = DB::transaction(function () use ($item, $target) {
            return DataSyncRecord::create([
                'id' => (string) Str::uuid(),
                'source_path' => $item->result_file,
                'target_path' => $target,
                'file_name' => basename($target),
                'status' => 'transferring',
                'retry_count' => 0,
                'max_retry' => 3,
                'started_at' => now(),
            ]);
        });

        // 2. Perform the sync outside the transaction
        try {
            $this->rsyncService->syncCrawlerFileToNas(
                $sourcePath,
                $target
            );

            Log::info('File sync orchestration successful', [
                'item_id' => $item->id,
                'target_path' => $target,
            ]);

            // 3. Update sync record status
            $record->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            // 4. Update the original CrawlerTaskItem status and public URL
            $publicUrl = \Illuminate\Support\Facades\Storage::url('nas/'.$fileName);
            $item->update([
                'status' => 'synced',
                'result_file' => $publicUrl,
            ]);

            return $target;

        } catch (Throwable $e) {
            Log::error('File sync orchestration failed', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            // 5. Update sync record status on failure
            $record->update([
                'status' => 'failed',
                'retry_count' => $record->retry_count + 1,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            // 6. Update the original CrawlerTaskItem to error
            $item->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
