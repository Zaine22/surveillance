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

    /**
     * Create a new job instance.
     */
    public $tries = 3;

    public $backoff = 30;

    public function __construct(public string $taskItemId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
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

        $response = Http::timeout(300)
            ->acceptJson() // Accept: application/json
            ->asJson()->post(
                config('services.python.url').'/api/crawler/crawl/direct',
                [
                    'task_item_id' => $taskItem->id,
                    'url' => $taskItem->url,
                ]
            );
        if ($response->successful()) {
            DB::table('crawler_task_items')
                ->where('id', $taskItem->id)
                ->update([
                    'status' => 'completed',
                    'updated_at' => now(),
                ]);
        } else {
            throw new \Exception('Crawling failed with status: '.$response->status());
        }

    }

    public function failed(Throwable $e): void
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
