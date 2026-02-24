<?php
namespace App\Services;

use App\Models\AiModelTask;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AiResultConsumeService
{
    protected string $stream   = 'ai:result:stream';
    protected string $group    = 'backend_ai_group';
    protected string $consumer = 'backend_ai_1';

    public function __construct(
        protected AiPredictResultService $resultService
    ) {}

    public function consume(): void
    {
        $this->createGroupIfNotExists();

        while (true) {

            $messages = Redis::xreadgroup(
                'GROUP', $this->group, $this->consumer,
                'BLOCK', 5000,
                'COUNT', 10,
                'STREAMS', $this->stream, '>'
            );

            if (empty($messages[$this->stream])) {
                continue;
            }

            foreach ($messages[$this->stream] as $messageId => $data) {

                try {

                    $taskId = $data['task_id'] ?? null;

                    if (! $taskId) {
                        Redis::xack($this->stream, $this->group, [$messageId]);
                        continue;
                    }

                    $task = AiModelTask::find($taskId);

                    if (! $task instanceof AiModelTask) {
                        Redis::xack($this->stream, $this->group, [$messageId]);
                        continue;
                    }

                    if ($task->status === 'completed') {
                        Redis::xack($this->stream, $this->group, [$messageId]);
                        continue;
                    }

                    $resultJson = $data['result'] ?? null;

                    if (! $resultJson) {
                        Redis::xack($this->stream, $this->group, [$messageId]);
                        continue;
                    }

                    $result = json_decode($resultJson, true);

                    if (! is_array($result)) {
                        Log::warning('Invalid AI result JSON', [
                            'task_id' => $taskId,
                            'payload' => $resultJson,
                        ]);
                        Redis::xack($this->stream, $this->group, [$messageId]);
                        continue;
                    }

                    $this->resultService->saveFromAiCallback($task, $result);

                    $task->status = 'completed';
                    $task->save();

                    Redis::xack($this->stream, $this->group, [$messageId]);

                } catch (\Throwable $e) {

                    Log::error('AI Result Consume Error', [
                        'error'      => $e->getMessage(),
                        'message_id' => $messageId,
                        'data'       => $data ?? [],
                    ]);

                }
            }
        }
    }

    protected function createGroupIfNotExists(): void
    {
        try {
            Redis::xgroup(
                'CREATE',
                $this->stream,
                $this->group,
                '0',
                'MKSTREAM'
            );
        } catch (\Throwable $e) {
        }
    }
}