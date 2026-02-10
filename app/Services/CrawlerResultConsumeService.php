<?php

namespace App\Services;

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
                    if (! empty($data['error_message'])) {
                        $this->taskManagerService->crawlerFailed(
                            (string) $data['task_item_id'],
                            (string) $data['error_message']
                        );
                    } else {
                        $this->taskManagerService->crawlerCompleted(
                            (string) $data['task_item_id'],
                            (string) $data['crawler_machine'],
                            (string) ($data['result_file'] ?? '')
                        );
                    }

                    Redis::xack($this->stream, $this->group, [$id]);

                } catch (\Throwable $e) {
                    Log::error('Result consume failed', [
                        'redis_id' => $id,
                        'payload' => $data,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
