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
                return is_array($keywords)
                    ? $keywords
                    : json_decode($keywords, true);
            })
            ->filter()
            ->values();

        $sources = [];

        if (! empty($config->sources)) {
            $sources = is_array($config->sources)
                ? $config->sources
                : json_decode($config->sources, true) ?? [];
        }

        foreach ($sources as $source) {
            foreach ($keywords as $keyword) {

                $item = CrawlerTaskItem::create([
                    'task_id'        => (string) $task->id,
                    'keywords'       => json_encode(array_values($keyword)),
                    'status'         => 'pending',
                    'crawl_location' => $source,
                    'error_message'  => null,
                ]);
                $this->dispatchService->dispatch($item);

                $item->update([
                    'status' => 'crawling',
                ]);
            }
        }

    }
    public function start(CrawlerTaskItem $item): array
    {
        if ($item->status !== 'pending') {
            return [
                'success' => false,
                'message' => 'Only pending items can be started.',
            ];
        }

        $item->update([
            'status' => 'crawling',
        ]);

        $this->dispatchService->dispatch($item);

        return [
            'success'      => true,
            'message'      => 'Task item started successfully.',
            'task_item_id' => $item->id,
            'status'       => $item->status,
        ];
    }

    public function pause(CrawlerTaskItem $item): array
    {
        if (! in_array($item->status, ['crawling', 'syncing'])) {
            return [
                'success' => false,
                'message' => 'Only crawling or syncing items can be paused.',
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
        if ($item->status !== 'error') {
            return [
                'success' => false,
                'message' => 'Only failed items can be retried.',
            ];
        }

        $item->update([
            'status'        => 'pending',
            'error_message' => null,
            'result_file'   => null,
        ]);

        $this->dispatchService->dispatch($item);

        return [
            'success'      => true,
            'message'      => 'Task item retried successfully.',
            'task_item_id' => $item->id,
            'status'       => $item->status,
        ];
    }
    public function delete($item): array
    {
        if (strtolower($item->status) === 'crawling') {
            return [
                'success' => false,
                'message' => 'Cannot delete item while crawling.',
            ];
        }

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
                'message'      => 'Task item deleted successfully.',
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
