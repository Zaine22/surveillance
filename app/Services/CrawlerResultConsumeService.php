<?php
namespace App\Services;

use App\Services\TaskManagerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CrawlerResultConsumeService
{
    protected string $stream = 'crawler:result:stream';

    protected string $group = 'backend_group';

    protected string $consumer = 'backend_1';

    public function __construct(
        protected TaskManagerService $taskManagerService
    ) {}

    public function consume(): void
    {
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

        Log::info('CrawlerResultConsumeService started');

        while (true) {

            try {

                $messages = Redis::executeRaw([
                    'XREADGROUP',
                    'GROUP', $this->group, $this->consumer,
                    'COUNT', 10,
                    'BLOCK', 5000,
                    'STREAMS', $this->stream, '>',
                ]);

                if (empty($messages)) {
                    continue;
                }

                foreach ($messages[0][1] as $message) {

                    $id = $message[0];

                    $raw  = $message[1];
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

                        Redis::executeRaw([
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

            } catch (\Throwable $e) {

                Log::error('Crawler stream read failed', [
                    'error' => $e->getMessage(),
                ]);

                sleep(2);
            }
        }
    }
}
