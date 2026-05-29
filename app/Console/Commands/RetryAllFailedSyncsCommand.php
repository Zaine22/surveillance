<?php

namespace App\Console\Commands;

use App\Jobs\RetryFailedSyncJob;
use App\Models\DataSyncRecord;
use Illuminate\Console\Command;

class RetryAllFailedSyncsCommand extends Command
{
    protected $signature = 'sync:retry-all {--force : Skip confirmation}';

    protected $description = 'Retry all failed sync records that haven\'t exceeded max retries';

    public function handle(): int
    {
        $failedRecords = DataSyncRecord::where('status', 'failed')
            ->whereColumn('retry_count', '<', 'max_retry')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($failedRecords->isEmpty()) {
            $this->info('No failed sync records found to retry');
            return 0;
        }

        $this->info("Found {$failedRecords->count()} failed sync records");

        $this->table(
            ['ID', 'Source', 'Retries', 'Error', 'Created'],
            $failedRecords->take(10)->map(fn($r) => [
                substr($r->id, 0, 12) . '...',
                basename($r->source_path),
                "{$r->retry_count}/{$r->max_retry}",
                substr($r->error_message ?? '', 0, 40),
                $r->created_at->format('Y-m-d H:i'),
            ])
        );

        if ($failedRecords->count() > 10) {
            $this->info("... and " . ($failedRecords->count() - 10) . " more");
        }

        if (!$this->option('force') && !$this->confirm("Retry all {$failedRecords->count()} failed syncs?")) {
            $this->info('Cancelled');
            return 0;
        }

        $bar = $this->output->createProgressBar($failedRecords->count());
        $bar->start();

        $dispatched = 0;
        foreach ($failedRecords as $record) {
            RetryFailedSyncJob::dispatch($record);
            $dispatched++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Dispatched {$dispatched} retry jobs");
        $this->info("Jobs will be processed by Horizon with exponential backoff");

        return 0;
    }
}
