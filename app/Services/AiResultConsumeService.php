<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AiResultConsumeService
{
    protected string $stream   = 'task:finished';
    protected string $group    = 'backend_ai_group';
    protected string $consumer = 'backend_ai_1';

    public function __construct(
        protected AiPredictResultService $resultService
    ) {}

    public function consume(): void
    {
        $redis = Redis::connection('ai');
        Log::info('AiResultConsumeService started', [
            'stream'   => $this->stream,
            'group'    => $this->group,
            'consumer' => $this->consumer,
        ]);

        while (true) {
            try {

                $messages = $redis->xreadgroup(
                    $this->group,
                    $this->consumer,
                    [$this->stream => '>'],
                    10,
                    5000
                );

                if ($messages === false || empty($messages[$this->stream])) {
                    continue;
                }

                foreach ($messages[$this->stream] as $id => $data) {
                    Log::info('🔥 REDIS MESSAGE RECEIVED', [
                        'id'   => $id,
                        'data' => $data,
                    ]);
                    $this->handleMessage($redis, $id, $data);
                }
            } catch (\Throwable $e) {
                Log::critical('AI consumer crashed', [
                    'error' => $e->getMessage(),
                ]);

                sleep(2);
            }
        }
    }

    protected function handleMessage($redis, string $redisId, array $data): void
    {
        try {
            $rawPayload = json_decode($data['payload'], true) ?? [];

            $taskId = $rawPayload['task_id'] ?? $data['task_id'] ?? null;

            if (! $taskId) {
                throw new \RuntimeException('task_id missing');
            }

            $parsed = $this->parseAiResult($rawPayload);

            $payload = array_merge($rawPayload, $parsed);

            $this->resultService->saveFromAiCallback((string) $taskId, $payload);

            $redis->xack($this->stream, $this->group, [$redisId]);
        } catch (\Throwable $e) {
            Log::error('AI result process failed', [
                'redis_id' => $redisId,
                'raw_data' => $data,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    protected function decodePayload(array $data): array
    {
        if (! empty($data['payload'])) {
            $decoded = json_decode($data['payload'], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return $data;
    }
    protected function parseAiResult(array $payload): array
    {
        if (empty($payload['result'])) {
            return [];
        }

        $raw = $payload['result'];

        $json = str_replace("'", '"', $raw);

        $json = str_replace(['True', 'False'], ['true', 'false'], $json);

        $json = preg_replace('/\((.*?)\)/', '[$1]', $json);

        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('AI parse failed', [
                'error' => json_last_error_msg(),
                'raw'   => $raw,
            ]);
            return [];
        }

        return $decoded;
    }
}
