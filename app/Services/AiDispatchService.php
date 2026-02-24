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

        Redis::hMSet("ai_task:{$task->id}", [
            'status'    => 'pending',
            'params'    => json_encode($params, JSON_UNESCAPED_UNICODE),
            'timestamp' => now()->toDateTimeString(),
        ]);

        Redis::xadd($this->stream, '*', [
            'event'   => 'new',
            'task_id' => (string) $task->id,
        ]);
    }
}