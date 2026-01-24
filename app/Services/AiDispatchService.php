<?php

namespace App\Services;

use App\Models\AiModelTask;
use Illuminate\Support\Facades\Redis;

class AiDispatchService
{
    protected string $stream = 'ai:task:stream';

    public function __construct() {}

    public function dispatch(AiModelTask $task, string $filePath): void
    {
        Redis::xadd($this->stream, '*', [
            'ai_model_task_id' => $task->id,
            'ai_model_id' => $task->ai_model_id,
            'file_path' => $filePath,
        ]);
    }
}
