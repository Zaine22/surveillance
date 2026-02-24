<?php
namespace App\Services;

use App\Models\CrawlerConfig;
use App\Models\CrawlerTask;
use App\Models\CrawlerTaskItem;
use App\Models\Lexicon;
use App\Services\CrawlerDispatchService;

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
            }
        }

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
        ]);

        $this->dispatchService->dispatch($item);

        return [
            'success'      => true,
            'message'      => 'Task item retried successfully.',
            'task_item_id' => $item->id,
            'status'       => $item->status,
        ];
    }
    public function delete(CrawlerTaskItem $item): array
    {
        if ($item->status === 'crawling') {
            return [
                'success' => false,
                'message' => 'Cannot delete item while crawling.',
            ];
        }

        $id = $item->id;

        $item->delete();

        return [
            'success'      => true,
            'message'      => 'Task item deleted successfully.',
            'task_item_id' => $id,
        ];
    }
}