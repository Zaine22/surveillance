<?php
namespace App\Services;

use App\Models\AiModelTask;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AiDispatchService
{
    protected string $stream = 'task:new';

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

        $redis->xadd('task:new', '*', [
            'event'   => 'new',
            'task_id' => (string) $task->id,
        ]);
    }

    // public function dispatch(AiModelTask $task, array $params): void
    // {
    //     $key = "task:{$task->id}";

    //     $redis = Redis::connection('ai');

    //     $payload = [
    //         'status'    => 'pending',
    //         'params'    => json_encode($params),
    //         'timestamp' => now()->toDateTimeString(),
    //         'result'    => '',
    //     ];

    //     foreach ($payload as $field => $value) {
    //         $redis->hset($key, $field, $value);
    //     }

    //     $streamId = $redis->xadd($this->stream, '*', [
    //         'event'   => 'new',
    //         'task_id' => (string) $task->id,
    //     ]);

    //     Log::info('AI task pushed to Redis', [
    //         'task_id' => $task->id,
    //         'redis_key' => $key,
    //         'stream' => $this->stream,
    //         'stream_id' => $streamId,
    //     ]);
    // }
}
