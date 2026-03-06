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
        return DB::transaction(function () use ($item) {

            $fileName = basename($item->result_file);

            $target = storage_path('app/public/nas/' . $fileName);

            $record = DataSyncRecord::create([
                'id'          => (string) Str::uuid(),
                'source_path' => $item->result_file,
                'target_path' => $target,
                'file_name'   => $fileName,
                'status'      => 'transferring',
                'retry_count' => 0,
                'max_retry'   => 3,
                'started_at'  => now(),
            ]);

            try {
                $this->rsyncService->syncCrawlerFileToNas(
                    $item->result_file,
                    $target
                );

                Log::info('File sync orchestration successful', [
                    'item_id' => $item->id,
                    'target_path' => $target,
                ]);

                $record->update([
                    'status'      => 'completed',
                    'finished_at' => now(),
                ]);

                return $target;

            } catch (Throwable $e) {
                Log::error('File sync orchestration failed', [
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);

                $record->update([
                    'status'        => 'failed',
                    'retry_count'   => $record->retry_count + 1,
                    'error_message' => $e->getMessage(),
                    'finished_at'   => now(),
                ]);

                throw $e;
            }
        });
    }
}
