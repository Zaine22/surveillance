<?php
namespace App\Services;

use App\Models\AiModelTask;
use Illuminate\Support\Facades\Redis;

class AiDispatchService
{
    protected string $stream = 'ai:task:stream';

    public function __construct()
    {}

    public function dispatch(AiModelTask $task, array $params): void
    {
        $key = "task:{$task->id}";

        $redis = Redis::connection('ai');

        $redis->hset($key, [
            'status'    => 'pending',
            'params'    => json_encode($params),
            'timestamp' => now()->toDateTimeString(),
            'result'    => '',
        ]);

        $redis->xadd('ai:task:stream', '*', [
            'event'   => 'new',
            'task_id' => (string) $task->id,
        ]);
    }
}
