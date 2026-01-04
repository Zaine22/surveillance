<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CrawlTaskItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $tries = 3;

    public $backoff = 30;

    public function __construct(public string $taskItemId)
    {
        $taskItem = DB::table('crawler_task_items')
            ->where('id', $this->taskItemId)
            ->first();

        if (! $taskItem || $taskItem->status !== 'pending') {
            return;
        }

        DB::table('crawler_task_items')
            ->where('id', $taskItem->id)
            ->update([
                'status' => 'processing',
                'updated_at' => now(),
            ]);

        Http::timeout(300)->post(
            'http://45.77.241.149/api/crawler/crawl/direct',
            [
                'task_item_id' => $taskItem->id,
                'url' => $taskItem->url,
            ]
        );
    }

    /**
     * Execute the job.
     */
    public function handle(\Throwable $e)
    {
        DB::table('crawler_task_items')
            ->where('id', $this->taskItemId)
            ->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'updated_at' => now(),
            ]);
    }
}
