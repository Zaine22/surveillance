<?php

namespace App\Console\Commands;

use App\Jobs\RetryFailedSyncJob;
use App\Models\DataSyncRecord;
use Illuminate\Console\Command;

class RetrySyncCommand extends Command
{
    protected $signature = 'sync:retry {record_id? : The ID of the sync record to retry}';

    protected $description = 'Retry a failed sync record';

    public function handle(): int
    {
        $recordId = $this->argument('record_id');

        if ($recordId) {
            // Retry specific record
            $record = DataSyncRecord::find($recordId);

            if (!$record) {
                $this->error("Sync record not found: {$recordId}");
                return 1;
            }

            if ($record->status === 'completed') {
                $this->warn("Record {$recordId} is already completed");
                return 0;
            }

            $this->info("Retrying sync record: {$recordId}");
            RetryFailedSyncJob::dispatch($record);
            $this->info("Retry job dispatched for record: {$recordId}");

            return 0;
        }

        // Interactive mode - show failed records
        $failedRecords = DataSyncRecord::where('status', 'failed')
            ->where('retry_count', '<', 'max_retry')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        if ($failedRecords->isEmpty()) {
            $this->info('No failed sync records found');
            return 0;
        }

        $this->table(
            ['ID', 'Source', 'Status', 'Retries', 'Error', 'Created'],
            $failedRecords->map(fn($r) => [
                substr($r->id, 0, 8) . '...',
                basename($r->source_path),
                $r->status,
                "{$r->retry_count}/{$r->max_retry}",
                substr($r->error_message ?? '', 0, 50),
                $r->created_at->format('Y-m-d H:i'),
            ])
        );

        if ($this->confirm('Retry all failed records?')) {
            foreach ($failedRecords as $record) {
                RetryFailedSyncJob::dispatch($record);
                $this->info("Dispatched retry for: " . substr($record->id, 0, 8));
            }

            $this->info("Dispatched {$failedRecords->count()} retry jobs");
        }

        return 0;
    }
}
