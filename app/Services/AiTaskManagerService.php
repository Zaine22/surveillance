<?php
namespace App\Services;

use App\Models\AiModel;
use App\Models\AiModelTask;
use App\Models\CrawlerTaskItem;
use App\Services\AiDispatchService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiTaskManagerService
{
    public function __construct(
        protected AiDispatchService $dispatchService
    ) {}

    public function createFromCrawlerItem(CrawlerTaskItem $item): AiModelTask
    {

        Log::info('here is createFromCrawlerItem');

        return DB::transaction(function () use ($item) {

            $existing = AiModelTask::where(
                'crawler_task_item_id',
                $item->id
            )->firstOrFail();

            if ($existing) {
                return $existing;
            }

            $model = AiModel::where('status', 'enabled')->firstOrFail();
            dd($model);
            $task  = AiModelTask::create([
                'id'                   => (string) Str::uuid(),
                'ai_model_id'          => $model->id,
                'crawler_task_item_id' => $item->id,
                'file_name'            => basename($item->result_file),
                'status'               => 'pending',
            ]);

            $params = [
                'dir_path'   => $item->result_file,
                'image_type' => 'element',
            ];

            try {
                $this->dispatchService->dispatch($task, $params);

                $task->update([
                    'status' => 'processing',
                ]);

            } catch (\Throwable $e) {
                Log::error('AI dispatch failed', [
                    'task_id' => $task->id,
                    'error'   => $e->getMessage(),
                ]);

                $task->update([
                    'status' => 'failed',
                ]);

                throw $e;
            }

            return $task;
        });
    }
}
