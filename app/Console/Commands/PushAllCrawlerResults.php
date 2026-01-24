<?php

namespace App\Console\Commands;

use App\Models\CrawlerTaskItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class PushAllCrawlerResults extends Command
{
    protected $signature = 'crawler:push-all-results
                            {status=synced : synced|error}';

    protected $description = 'Push crawler results for ALL task items in DB';

    public function handle(): int
    {
        $status = $this->argument('status');

        $items = CrawlerTaskItem::all();

        if ($items->isEmpty()) {
            $this->warn('No crawler task items found');

            return Command::SUCCESS;
        }

        $this->info("Found {$items->count()} crawler task items");

        foreach ($items as $item) {

            $payload = [
                'task_item_id' => (string) $item->id,
                'task_id' => (string) $item->task_id,
                'status' => $status,
            ];

            if ($status === 'synced') {
                $payload['result_file'] =
                    '/results/'.$item->id.'.json';
            } else {
                $payload['error_message'] =
                    'Simulated crawler failure';
            }

            Redis::xadd(
                'crawler:result:stream',
                '*',
                $payload
            );
        }

        $this->info(' Result pushed for ALL crawler task items');

        return Command::SUCCESS;
    }
}
