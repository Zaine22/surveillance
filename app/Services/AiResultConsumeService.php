<?php

namespace App\Services;

use App\Models\AiModelTask;
use Illuminate\Support\Facades\Redis;

class AiResultConsumeService
{
    public function __construct(
        protected AiPredictResultService $resultService
    ) {}

    protected string $stream = 'ai:result:stream';

    protected string $group = 'backend_ai_group';

    public function consume(): void
    {
        Redis::xgroup('CREATE', $this->stream, $this->group, '$', true);

        while (true) {
            $messages = Redis::xreadgroup(
                'GROUP', $this->group, 'backend_ai_1',
                'BLOCK', 5000,
                'COUNT', 10,
                'STREAMS', $this->stream, '>'
            );

            foreach ($messages[$this->stream] ?? [] as $id => $data) {

                $task = AiModelTask::find($data['ai_model_task_id']);
                if ($task) {
                    $this->resultService->saveFromAiCallback($task, $data);
                    $task->update(['status' => 'completed']);
                }

                Redis::xack($this->stream, $this->group, [$id]);
            }
        }
    }
}
