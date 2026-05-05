<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AiResultConsumeService
{
    protected string $stream    = 'task:finished';
    protected string $lastIdKey = 'backend:last_task_finished_id';

    public function __construct(
        protected AiPredictResultService $resultService
    ) {}

    // public function consume(): void
    // {
    //     $redis = Redis::connection('ai');
    //     Log::info('AiResultConsumeService started', [
    //         'stream'   => $this->stream,
    //         'group'    => $this->group,
    //         'consumer' => $this->consumer,
    //     ]);

    //     while (true) {
    //         try {

    //             $messages = $redis->xreadgroup(
    //                 $this->group,
    //                 $this->consumer,
    //                 [$this->stream => '>'],
    //                 10,
    //                 5000
    //             );

    //             if ($messages === false || empty($messages[$this->stream])) {
    //                 continue;
    //             }

    //             foreach ($messages[$this->stream] as $id => $data) {
    //                 Log::info('🔥 REDIS MESSAGE RECEIVED', [
    //                     'id'   => $id,
    //                     'data' => $data,
    //                 ]);
    //                 $this->handleMessage($redis, $id, $data);
    //             }
    //         } catch (\Throwable $e) {
    //             Log::critical('AI consumer crashed', [
    //                 'error' => $e->getMessage(),
    //             ]);

    //             sleep(2);
    //         }
    //     }
    // }
    public function consume(): void
    {
        $redis = Redis::connection('ai');

        Log::info('AiResultConsumeService started', [
            'stream' => $this->stream,
            'mode'   => 'xread_without_group',
        ]);

        $lastId = $redis->get($this->lastIdKey) ?: '0-0';

        Log::info('AI result consumer last ID', [
            'last_id' => $lastId,
        ]);

        while (true) {
            try {
                $messages = $redis->xread(
                    [$this->stream => $lastId],
                    10,
                    5000
                );

                if (empty($messages) || empty($messages[$this->stream])) {
                    continue;
                }

                foreach ($messages[$this->stream] as $redisId => $data) {
                    Log::info('REDIS TASK FINISHED MESSAGE RECEIVED', [
                        'redis_id' => $redisId,
                        'data'     => $data,
                    ]);

                    $this->handleMessage($redis, $redisId, $data);

                    $lastId = $redisId;

                    $redis->set($this->lastIdKey, $lastId);

                    Log::info('AI result stream ID saved', [
                        'last_id' => $lastId,
                    ]);
                }

            } catch (\Throwable $e) {
                Log::critical('AI consumer crashed', [
                    'error' => $e->getMessage(),
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine(),
                ]);

                sleep(2);
            }
        }
    }

    // protected function handleMessage($redis, string $redisId, array $data): void
    // {
    //     try {
    //         $rawPayload = json_decode($data['payload'], true) ?? [];

    //         $taskId = $rawPayload['task_id'] ?? $data['task_id'] ?? null;

    //         if (! $taskId) {
    //             throw new \RuntimeException('task_id missing');
    //         }

    //         $parsed = $this->parseAiResult($rawPayload);

    //         $payload = array_merge($rawPayload, $parsed);

    //         $this->resultService->saveFromAiCallback((string) $taskId, $payload);

    //         $redis->xack($this->stream, $this->group, [$redisId]);
    //     } catch (\Throwable $e) {
    //         Log::error('AI result process failed', [
    //             'redis_id' => $redisId,
    //             'raw_data' => $data,
    //             'error'    => $e->getMessage(),
    //         ]);
    //     }
    // }

    protected function handleMessage($redis, string $redisId, array $data): void
    {
        try {
            $taskId = $data['task_id'] ?? null;
            $event  = $data['event'] ?? null;

            if (! $taskId) {
                Log::error('AI result task_id missing', [
                    'redis_id' => $redisId,
                    'data'     => $data,
                ]);

                return;
            }

            $taskKey = "task:{$taskId}";

            $taskPayload = $redis->hgetall($taskKey);

            if (empty($taskPayload)) {
                Log::error('AI result task hash not found', [
                    'redis_id' => $redisId,
                    'task_id'  => $taskId,
                    'task_key' => $taskKey,
                ]);

                return;
            }

            Log::info('AI result task hash loaded', [
                'redis_id' => $redisId,
                'task_id'  => $taskId,
                'event'    => $event,
                'status'   => $taskPayload['status'] ?? null,
            ]);

            $parsed = $this->parseAiResult($taskPayload);

            $payload = array_merge($taskPayload, [
                'event'   => $event,
                'task_id' => $taskId,
                'parsed'  => $parsed,
            ]);

            $this->resultService->saveFromAiCallback((string) $taskId, $payload);

            Log::info('AI result acknowledged', [
                'redis_id' => $redisId,
                'task_id'  => $taskId,
                'status'   => $taskPayload['status'] ?? null,
            ]);

        } catch (\Throwable $e) {
            Log::error('AI result process failed', [
                'redis_id' => $redisId,
                'raw_data' => $data,
                'error'    => $e->getMessage(),
                'file'     => $e->getFile(),
                'line'     => $e->getLine(),
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
    // protected function parseAiResult(array $payload): array
    // {
    //     if (empty($payload['result'])) {
    //         return [];
    //     }

    //     $raw = $payload['result'];

    //     $json = str_replace("'", '"', $raw);

    //     $json = str_replace(['True', 'False'], ['true', 'false'], $json);

    //     $json = preg_replace('/\((.*?)\)/', '[$1]', $json);

    //     $decoded = json_decode($json, true);

    //     if (json_last_error() !== JSON_ERROR_NONE) {
    //         Log::error('AI parse failed', [
    //             'error' => json_last_error_msg(),
    //             'raw'   => $raw,
    //         ]);
    //         return [];
    //     }

    //     return $decoded;
    // }

    protected function parseAiResult(array $payload): array
    {
        if (empty($payload['result'])) {
            return [];
        }

        $raw = $payload['result'];

        if (($payload['status'] ?? null) === 'failed') {
            return [
                'error_message' => $raw,
            ];
        }

        $json = str_replace("'", '"', $raw);

        $json = str_replace(
            ['True', 'False', 'None'],
            ['true', 'false', 'null'],
            $json
        );

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
