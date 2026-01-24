<?php

namespace App\Services;

use App\Models\CrawlerTaskItem;
use App\Models\DataSyncRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class DataSyncOrchestratorService
{
    public function __construct(
        protected RsyncService $rsyncService
    ) {}

    public function syncCrawlerFileToNas(CrawlerTaskItem $item): string
    {
        return DB::transaction(function () use ($item) {

            $target = '/nas/'.basename($item->result_file);

            $record = DataSyncRecord::create([
                'id' => (string) Str::uuid(),
                'source_path' => $item->result_file,
                'target_path' => $target,
                'file_name' => basename($item->result_file),
                'status' => 'transferring',
                'retry_count' => 0,
                'max_retry' => 3,
                'started_at' => now(),
            ]);

            try {
                $this->rsyncService->sync(
                    $item->result_file,
                    $target
                );

                $record->update([
                    'status' => 'completed',
                    'finished_at' => now(),
                ]);

                return $target;

            } catch (Throwable $e) {

                $record->update([
                    'status' => 'error',
                    'retry_count' => $record->retry_count + 1,
                    'error_message' => $e->getMessage(),
                    'finished_at' => now(),
                ]);

                throw $e;
            }
        });
    }
}
