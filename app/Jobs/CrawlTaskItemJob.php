<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class CrawlTaskItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // ✅ retry only once
    public $tries = 10;

    // ✅ job-level timeout (must be < worker timeout)
    public $timeout = 420; // 7 minutes

    // ✅ progressive backoff
    public $backoff = [120, 300]; // 2 min, then 5 min

    public function __construct(public string $taskItemId) {}

    public function handle(): void
    {

        $updated = DB::table('crawler_task_items')
            ->where('id', $this->taskItemId)
            ->where('status', 'pending')
            ->update([
                'status' => 'processing',
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return;
        }

        $taskItem = DB::table('crawler_task_items')
            ->where('id', $this->taskItemId)
            ->first();

        Http::timeout(5)
            ->acceptJson()
            ->asJson()
            ->post(
                config('services.python.url').'/api/crawler/crawl/direct',
                [
                    'task_item_id' => $taskItem->id,
                    'url' => $taskItem->url,
                ]
            );

    }

    public function failed(Throwable $e): void
    {
        DB::table('crawler_task_items')
            ->where('id', $this->taskItemId)
            ->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 2000),
                'updated_at' => now(),
            ]);
    }
}
