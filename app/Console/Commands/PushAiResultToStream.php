<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PushAiResultToStream extends Command
{
    protected $signature = 'ai:push
                            {--status=finished : finished|failed|pending|running}';

    protected $description = 'Push fake AI result into Redis stream + create ai_model_task';

    protected string $stream = 'ai:result:stream';

    public function handle(): int
    {
        $status = strtolower($this->option('status'));

        // 🔥 1. create ai_model_task first
        $taskId = $this->createAiModelTask();

        // 🔥 2. build payload
        $payload = $this->buildPayload($taskId, $status);

        // 🔥 3. push to Redis
        $messageId = Redis::xadd($this->stream, '*', [
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        $this->info("✅ AI task created + result pushed");
        $this->line("Task ID: {$taskId}");
        $this->line("Stream: {$this->stream}");
        $this->line("Message ID: {$messageId}");
        $this->line("Status: {$status}");

        return Command::SUCCESS;
    }

    /**
     * 🔥 Create ai_model_tasks record
     */
    protected function createAiModelTask(): string
    {
        $taskId = (string) Str::uuid();

        // 👉 get random ai_model_id
        $aiModelId = DB::table('ai_models')->value('id');

        // 👉 get random crawler_task_item_id
        $crawlerItemId = DB::table('crawler_task_items')->value('id');

        if (!$aiModelId || !$crawlerItemId) {
            throw new \RuntimeException('ai_models or crawler_task_items is empty');
        }

        DB::table('ai_model_tasks')->insert([
            'id' => $taskId,
            'ai_model_id' => $aiModelId,
            'crawler_task_item_id' => $crawlerItemId,
            'file_name' => 'task/test_3.zip',
            'status' => 'completed', // since we push result directly
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $taskId;
    }

    protected function buildPayload(string $taskId, string $status): array
    {
        return match ($status) {

            'finished' => [
                'task_id' => $taskId,
                'status'  => 'finished',
                'timestamp' => now()->toDateTimeString(),
                'params' => json_encode([
                    'dir_path' => 'test_3',
                    'image_type' => 'screenshot',
                ]),
                'result' => $this->getSuccessResult(),
            ],

            'failed' => [
                'task_id' => $taskId,
                'status'  => 'failed',
                'timestamp' => now()->toDateTimeString(),
                'params' => json_encode([
                    'dir_path' => 'test_5',
                    'image_type' => 'element',
                ]),
                'result' => 'task/test_5.zip or task/test_5 not found',
            ],

            'pending' => [
                'task_id' => $taskId,
                'status'  => 'pending',
                'timestamp' => now()->toDateTimeString(),
                'params' => json_encode([
                    'dir_path' => 'test_3',
                    'image_type' => 'screenshot',
                ]),
                'result' => '',
            ],

            'running' => [
                'task_id' => $taskId,
                'status'  => 'running',
                'timestamp' => now()->toDateTimeString(),
                'params' => json_encode([
                    'dir_path' => 'test_3',
                    'image_type' => 'screenshot',
                ]),
                'result' => '',
            ],

            default => throw new \InvalidArgumentException("Invalid status: {$status}"),
        };
    }

    protected function getSuccessResult(): string
    {
        return "{'victim': [{'image': 'task/test_3/275.png', 'victims': [{'user_name': 'victim_2_1', 'facial_area': {'x': 78, 'y': 32, 'w': 43, 'h': 57}, 'similarity': 0.8018992902289805}]}], 'age': [{'underage_probability': 0.9985, 'success': True, 'path': '/home/victor/MOHW/task/test_3/112.png'}], 'nsfw': [{'image': 'task/test_3/112.png', 'result': {'porn': 0.889}}]}";
    }
}
