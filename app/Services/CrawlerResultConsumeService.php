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
            // group already exists
        }

        Log::info('CrawlerResultConsumeService started');

        while (true) {
            $messages = Redis::xreadgroup(
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
                            (string) $payload['error_message']
                        );
                    } else {
                        $this->taskManagerService->crawlerCompleted(
                            (string) $taskItemId,
                            (string) ($payload['result_file'] ?? 'not found'),
                        );
                    }

                    Redis::xack($this->stream, $this->group, [$id]);

                } catch (\Throwable $e) {
                    Log::error('Result consume failed', [
                        'redis_id' => $id,
                        'raw_data' => $data,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }

        }
    }
}
