<?php

namespace App\Services;

use App\Models\CrawlerTaskItem;
use Illuminate\Support\Facades\Redis;

class CrawlerDispatchService
{
    protected string $stream = 'crawler:task:stream';

    public function dispatch(array $payload): void
    {
        Redis::xadd(
            $this->stream,
            '*',
            $payload
        );
    }
}
