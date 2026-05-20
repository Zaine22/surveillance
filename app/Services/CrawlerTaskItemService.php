<?php
namespace App\Services;

use App\Models\CrawlerConfig;
use App\Models\CrawlerTask;
use App\Models\CrawlerTaskItem;
use App\Models\Lexicon;
use App\Services\CrawlerDispatchService;
use Illuminate\Support\Facades\DB;

class CrawlerTaskItemService
{
    public function __construct(
        protected CrawlerDispatchService $dispatchService,
    ) {}

    public function createFromTask(CrawlerTask $task, CrawlerConfig $config, Lexicon $lexicon): void
    {

        $keywords = $lexicon->keywords()
            ->where('status', 'enabled')
            ->pluck('keywords')
            ->map(function ($keywords) {

                $decoded = is_array($keywords)
                    ? $keywords
                    : json_decode($keywords, true);

                return is_array($decoded) ? array_values($decoded) : [];
            })
            ->filter(function ($item) {
                return ! empty($item);
            })
            ->values()
            ->toArray();

        $sources = [];

        if (! empty($config->sources)) {
            $sources = is_array($config->sources)
                ? $config->sources
                : json_decode($config->sources, true) ?? [];
        }

        foreach ($sources as $source) {
            foreach ($keywords as $keywordGroup) {

                $item = CrawlerTaskItem::create([
                    'task_id'        => (string) $task->id,
                    'keywords'       => $keywordGroup,
                    'status'         => 'crawling',
                    'crawl_location' => $source,
                    'error_message'  => null,
                ]);

                $this->dispatchService->dispatch($item);

            }
        }

    }
    public function start(CrawlerTaskItem $item): array
    {
        if (!in_array($item->status, ['pending', 'error', 'failed'])) {
            return [
                'success' => false,
                // 'message' => 'Only pending items can be started.',
                'message' => '只有待處理的項目才能開始。',
            ];
        }

        $item->update([
            'status' => 'crawling',
            'error_message' => null,
        ]);

        $this->dispatchService->dispatch($item);

        return [
            'success'      => true,
            // 'message'      => 'Task item started successfully.',
            'message'      => '任務項目已成功開始。',
            'task_item_id' => $item->id,
            'status'       => $item->status,
        ];
    }

    public function pause(CrawlerTaskItem $item): array
    {
        if (! in_array($item->status, ['crawling', 'syncing'])) {
            return [
                'success' => false,
                // 'message' => 'Only crawling or syncing items can be paused.',
                'message' => '只有正在爬取或同步的項目才能暫停。',
            ];
        }

        $this->dispatchService->dispatchPauseItems($item);

        $item->update([
            'status' => 'pending',
        ]);

        return [
            'success'      => true,
            'message'      => 'Crawling paused.',
            'task_item_id' => $item->id,
            'status'       => $item->status,
        ];
    }

    public function retry(CrawlerTaskItem $item): array
    {
        if (! in_array($item->status, ['error', 'failed'])) {
            return [
                'success' => false,
                // 'message' => 'Only failed items can be retried.',
                'message' => '只有失敗的項目才能重試。',
            ];
        }

        $item->update([
            'status'        => 'crawling',
            'error_message' => null,
            'result_file'   => null,
        ]);

        $this->dispatchService->dispatch($item);

        return [
            'success'      => true,
            // 'message'      => 'Task item retried successfully.',
            'message'      => '任務項目已成功重試。',
            'task_item_id' => $item->id,
            'status'       => $item->status,
        ];
    }
    public function delete($item): array
    {
        $this->dispatchService->dispatchPauseItems($item);
        $id = $item->id;

        try {
            DB::transaction(function () use ($item) {

                $taskIds = DB::table('ai_model_tasks')
                    ->where('crawler_task_item_id', $item->id)
                    ->pluck('id');

                if ($taskIds->isNotEmpty()) {
                    DB::table('ai_predict_results')
                        ->whereIn('ai_model_task_id', $taskIds)
                        ->delete();
                }

                DB::table('ai_model_tasks')
                    ->where('crawler_task_item_id', $item->id)
                    ->delete();

                $item->delete();
            });

            return [
                'success'      => true,
                // 'message'      => 'Task item deleted successfully.',
                'message'      => '任務項目已成功刪除。',
                'task_item_id' => $id,
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage(),
            ];
        }
    }
}