<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProcessCrawlerTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tasks = DB::table('crawler_task_items')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->limit(10)
            ->get();

        if ($tasks->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($tasks as $taskItem) {

            try {
                $response = Http::timeout(10)
                    ->acceptJson()
                    ->asJson()
                    ->post(
                        config('services.python.url').'/api/crawler/crawl/direct',
                        [
                            'task_item_id' => $taskItem->id,
                            'url' => $taskItem->url,
                        ]
                    );

                // 4️⃣ handle failure
                if (! $response->successful()) {
                    throw new \Exception(
                        'Python crawler failed: '.$response->body()
                    );
                }

            } catch (\Throwable $e) {

                // 5️⃣ mark failed
                DB::table('crawler_task_items')
                    ->where('id', $taskItem->id)
                    ->update([
                        'status' => 'failed',
                        'error_message' => substr($e->getMessage(), 0, 2000),
                        'updated_at' => now(),
                    ]);
            }
        }

        return self::SUCCESS;
    }
}
