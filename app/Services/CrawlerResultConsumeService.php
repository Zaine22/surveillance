<?php
namespace App\Services;

use App\Services\TaskManagerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CrawlerResultConsumeService
{
    protected string $stream = 'crawler:result:stream';

    protected string $group = 'backend_group';

    protected string $consumer;

    public function __construct(
        protected TaskManagerService $taskManagerService
    ) {

        $this->consumer = 'backend_' . gethostname() . '_' . uniqid();
    }

    // public function consume(): void
    // {

    //     try {
    //         Redis::executeRaw([
    //             'XGROUP', 'CREATE',
    //             $this->stream,
    //             $this->group,
    //             '0',
    //             'MKSTREAM',
    //         ]);
    //     } catch (\Throwable $e) {

    //     }

    //     Log::info('CrawlerResultConsumeService started', [
    //         'consumer' => $this->consumer,
    //     ]);

    //     $this->processPending();

    //     while (true) {
    //         try {
    //             $messages = Redis::executeRaw([
    //                 'XREADGROUP',
    //                 'GROUP', $this->group, $this->consumer,
    //                 'COUNT', 10,
    //                 'BLOCK', 5000,
    //                 'STREAMS', $this->stream, '>',
    //             ]);

    //             if (empty($messages)) {
    //                 continue;
    //             }

    //             $this->processMessages($messages);

    //         } catch (\Throwable $e) {

    //             Log::error('Crawler stream read failed', [
    //                 'error' => $e->getMessage(),
    //             ]);

    //             sleep(2);
    //         }
    //     }
    // }
    public function consume(): void
    {
        try {
            Redis::connection()->executeRaw([
                'XGROUP', 'CREATE',
                $this->stream,
                $this->group,
                '0',
                'MKSTREAM',
            ]);
        } catch (\Throwable $e) {
            // ignore BUSYGROUP
        }

        Log::info('CrawlerResultConsumeService started', [
            'consumer' => $this->consumer,
        ]);

        $this->processPending();

        while (true) {
            try {
                $messages = Redis::connection()->executeRaw([
                    'XREADGROUP',
                    'GROUP', $this->group, $this->consumer,
                    'COUNT', 10,
                    'BLOCK', 5000,
                    'STREAMS', $this->stream, '>',
                ]);

                if (empty($messages)) {
                    continue;
                }

                $this->processMessages($messages);

            } catch (\Throwable $e) {

                $msg = $e->getMessage();

                Log::error('Crawler stream read failed', [
                    'error' => $msg,
                ]);

                if (str_contains($msg, 'no longer exists')) {
                    try {
                        Redis::connection()->executeRaw([
                            'XGROUP', 'CREATE',
                            $this->stream,
                            $this->group,
                            '0',
                            'MKSTREAM',
                        ]);
                    } catch (\Throwable $e2) {
                        // ignore if already exists
                    }
                }

                sleep(2);
            }
        }
    }

    // protected function processPending(): void
    // {
    //     Log::info('Processing pending messages...');

    //     while (true) {
    //         $messages = Redis::executeRaw([
    //             'XREADGROUP',
    //             'GROUP', $this->group, $this->consumer,
    //             'COUNT', 10,
    //             'STREAMS', $this->stream, '0',
    //         ]);

    //         if (empty($messages)) {
    //             break;
    //         }

    //         $this->processMessages($messages);
    //     }
    // }

    // protected function processMessages(array $messages): void
    // {
    //     foreach ($messages[0][1] as $message) {

    //         $id  = $message[0];
    //         $raw = $message[1];

    //         $data = [];
    //         for ($i = 0; $i < count($raw); $i += 2) {
    //             $data[$raw[$i]] = $raw[$i + 1] ?? null;
    //         }

    //         try {

    //             $payload = [];

    //             if (! empty($data['payload'])) {
    //                 $payload = json_decode($data['payload'], true) ?? [];
    //             }

    //             $taskItemId = $payload['task_item_id'] ?? $data['task_item_id'] ?? null;

    //             if (! $taskItemId) {
    //                 throw new \RuntimeException('task_item_id missing');
    //             }

    //             if (! empty($payload['error_message'])) {

    //                 $this->taskManagerService->crawlerFailed(
    //                     (string) $taskItemId,
    //                     (string) $payload['error_message'],
    //                     (string) ($payload['crawler_machine'] ?? '')
    //                 );

    //             } else {

    //                 $this->taskManagerService->crawlerCompleted(
    //                     (string) $taskItemId,
    //                     (string) ($payload['result_file'] ?? 'not found'),
    //                     (string) ($payload['crawler_machine'] ?? '')
    //                 );
    //             }

    //         } catch (\Throwable $e) {

    //             Log::error('Crawler result processing failed', [
    //                 'redis_id' => $id,
    //                 'data'     => $data,
    //                 'error'    => $e->getMessage(),
    //             ]);

    //         } finally {
    //             Redis::executeRaw([
    //                 'XACK',
    //                 $this->stream,
    //                 $this->group,
    //                 $id,
    //             ]);
    //         }
    //     }
    // }

    protected function processPending(): void
    {
        Log::info('Processing pending messages...');

        while (true) {
            try {
                $messages = Redis::connection()->executeRaw([
                    'XREADGROUP',
                    'GROUP', $this->group, $this->consumer,
                    'COUNT', 10,
                    'STREAMS', $this->stream, '0',
                ]);

                if (empty($messages) || empty($messages[0][1])) {
                    break;
                }

                $this->processMessages($messages);

            } catch (\Throwable $e) {

                if (str_contains($e->getMessage(), 'no longer exists')) {
                    // recreate stream + group
                    try {
                        Redis::connection()->executeRaw([
                            'XGROUP', 'CREATE',
                            $this->stream,
                            $this->group,
                            '0',
                            'MKSTREAM',
                        ]);
                    } catch (\Throwable $e2) {}
                }

                break;
            }
        }
    }

    protected function processMessages(array $messages): void
    {
        if (empty($messages[0][1])) {
            return;
        }

        foreach ($messages[0][1] as $message) {

            $id  = $message[0];
            $raw = $message[1];

            $data = [];
            for ($i = 0; $i < count($raw); $i += 2) {
                $data[$raw[$i]] = $raw[$i + 1] ?? null;
            }

            try {

                $payload = [];

                if (! empty($data['payload'])) {
                    $payload = json_decode($data['payload'], true) ?? [];
                }

                $taskItemId = $payload['task_item_id'] ?? $data['task_item_id'] ?? null;

                if (! $taskItemId) {
                    throw new \RuntimeException('task_item_id missing');
                }

                if (! empty($payload['error_message'])) {

                    $this->taskManagerService->crawlerFailed(
                        (string) $taskItemId,
                        (string) $payload['error_message'],
                        (string) ($payload['crawler_machine'] ?? '')
                    );

                } else {

                    $this->taskManagerService->crawlerCompleted(
                        (string) $taskItemId,
                        (string) ($payload['result_file'] ?? 'not found'),
                        (string) ($payload['crawler_machine'] ?? '')
                    );
                }

                Redis::connection()->executeRaw([
                    'XACK',
                    $this->stream,
                    $this->group,
                    $id,
                ]);

            } catch (\Throwable $e) {

                Log::error('Crawler result processing failed', [
                    'redis_id' => $id,
                    'data'     => $data,
                    'error'    => $e->getMessage(),
                ]);
            }
        }
    }
}
