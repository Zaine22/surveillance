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
        protected BotMachineService $botMachineService
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

                $bot  = $this->botMachineService->getAvailableBot();
                $item = CrawlerTaskItem::create([
                    'task_id'         => (string) $task->id,
                    'keywords'        => json_encode(array_values($keyword)),
                    'status'          => 'pending',
                    'crawl_location'  => $source,
                    'crawler_machine' => $bot?->name,
                    'error_message'   => null,
                ]);
                $this->dispatchService->dispatch($item);
            }
        }

    }

    public function updateStatus(
        string $id,
        string $status,
        ?string $resultFile = null,
        ?string $error = null
    ): void {
        CrawlerTaskItem::where('id', $id)->update([
            'status'        => $status,
            'result_file'   => $resultFile,
            'error_message' => $error,
        ]);
    }
}
