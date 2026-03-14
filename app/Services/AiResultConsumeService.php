<?php

namespace App\Services;

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
        // Create group if not exists
        try {
            Redis::executeRaw([
                'XGROUP', 'CREATE',
                $this->stream,
                $this->group,
                '0',
                'MKSTREAM',
            ]);
        } catch (\Throwable $e) {
            // Group already exists
        }

        Log::info('AiResultConsumeService started');

        while (true) {
            try {

                $messages = Redis::xreadgroup(
                    $this->group,
                    $this->consumer,
                    [$this->stream => '>'],
                    10,   // COUNT
                    5     // BLOCK (seconds)
                );

                if ($messages === false || empty($messages[$this->stream])) {
                    continue;
                }

                foreach ($messages[$this->stream] as $id => $data) {

                    try {

                        $payload = [];

                        if (!empty($data['payload'])) {
                            $payload = json_decode($data['payload'], true) ?? [];
                        }

                        $taskId = $payload['task_id'] ?? $data['task_id'] ?? null;

                        if (!$taskId) {
                            throw new \RuntimeException('task_id missing');
                        }


                        $this->resultService->saveFromAiCallback((string) $taskId, $payload);


                        Redis::xack($this->stream, $this->group, [$id]);

                    } catch (\Throwable $e) {

                        Log::error('AI result process failed', [
                            'redis_id' => $id,
                            'raw_data' => $data,
                            'error'    => $e->getMessage(),
                        ]);
                    }
                }

            } catch (\Throwable $e) {

                Log::critical('AI consumer crashed', [
                    'error' => $e->getMessage(),
                ]);

                sleep(2); // prevent CPU spike if Redis down
            }
        }
    }
}
