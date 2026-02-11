<?php

namespace App\Services;

use App\Models\CrawlerTaskItem;
use Illuminate\Support\Facades\Redis;

class CrawlerDispatchService
{
    protected string $stream = 'crawler:task:stream';

    public function dispatch(CrawlerTaskItem $item): void
    {
        Redis::xadd(
            $this->stream,
            '*',
            [
                'task_item_id' => (string) $item->id,
                'keywords' => (string) $item->keywords,
                'crawl_location' => (string) $item->crawl_location,
                'type' => 'patrol',
            ]
        );
    }
}
