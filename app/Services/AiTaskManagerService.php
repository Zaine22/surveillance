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
            )->first();

            if ($existing) {
                return $existing;
            }

            $model = AiModel::where('status', 'enabled')->firstOrFail();

            $task = AiModelTask::create([
                'id'                   => (string) Str::uuid(),
                'ai_model_id'          => $model->id,
                'crawler_task_item_id' => $item->id,
                'file_name'            => basename($item->result_file),
                'status'               => 'pending',
            ]);

            $filePath           = parse_url($item->result_file, PHP_URL_PATH);
            $fileName           = basename($filePath);
            $fileNameWithoutZip = pathinfo($fileName, PATHINFO_FILENAME);

            // AI will receive the folder name and dynamic key for two-layer decryption
            // AI already knows the base URL and will construct URLs themselves
            $params = [
                'dir_path'    => $fileNameWithoutZip,
                'image_type'  => 'element',
                'dynamic_key' => $item->dynamic_key,
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
