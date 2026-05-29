<?php

namespace App\Jobs;

use App\Models\CrawlerTaskItem;
use App\Models\DataSyncRecord;
use App\Services\DataSyncOrchestratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RetryFailedSyncJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public DataSyncRecord $record,
    ) {}

    public function handle(DataSyncOrchestratorService $orchestrator): void
    {
        Log::info('RetryFailedSyncJob started', [
            'record_id' => $this->record->id,
            'retry_count' => $this->record->retry_count,
            'max_retry' => $this->record->max_retry,
        ]);

        // Check if max retries exceeded
        if ($this->record->retry_count >= $this->record->max_retry) {
            Log::warning('Max retries exceeded, giving up', [
                'record_id' => $this->record->id,
                'retry_count' => $this->record->retry_count,
            ]);
            return;
        }

        // Find the associated CrawlerTaskItem
        // Try to find by exact match first, then by URL pattern, then by filename
        $item = CrawlerTaskItem::where('result_file', $this->record->source_path)->first();

        if (!$item) {
            // Try with URL pattern
            $item = CrawlerTaskItem::where('result_file', 'like', '%' . basename($this->record->source_path))->first();
        }

        if (!$item) {
            // Try to find by just the filename (without URL)
            $filename = basename($this->record->source_path);
            $item = CrawlerTaskItem::where('result_file', $filename)->first();
        }

        if (!$item) {
            Log::error('CrawlerTaskItem not found for retry', [
                'record_id' => $this->record->id,
                'source_path' => $this->record->source_path,
            ]);
            return;
        }

        // Reset the item status to syncing
        $item->update([
            'status' => 'syncing',
            'error_message' => null,
        ]);

        // Update record status and increment retry count
        $this->record->update([
            'status' => 'retrying',
            'started_at' => now(),
        ]);

        // Increment retry count
        $this->record->increment('retry_count');
        $this->record->refresh();

        try {
            $orchestrator->syncUnscannedFileToMainWeb($item, $this->record);

            Log::info('Retry successful', [
                'record_id' => $this->record->id,
                'item_id' => $item->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('Retry failed', [
                'record_id' => $this->record->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
                'retry_count' => $this->record->retry_count,
            ]);

            // If we haven't exceeded max retries, schedule another retry
            if ($this->record->retry_count < $this->record->max_retry) {
                // Exponential backoff: 5 min, 15 min, 45 min
                $delayMinutes = 5 * pow(3, $this->record->retry_count - 1);

                Log::info('Scheduling next retry', [
                    'record_id' => $this->record->id,
                    'delay_minutes' => $delayMinutes,
                    'next_retry_at' => now()->addMinutes($delayMinutes),
                ]);

                RetryFailedSyncJob::dispatch($this->record)
                    ->delay(now()->addMinutes($delayMinutes));
            }
        }
    }
}
