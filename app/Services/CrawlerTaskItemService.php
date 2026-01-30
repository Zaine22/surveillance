<?php

namespace App\Services;

use App\Models\CrawlerConfig;
use App\Models\CrawlerTask;
use App\Models\CrawlerTaskItem;
use App\Models\Lexicon;

class CrawlerTaskItemService
{
    public function __construct(
        protected CrawlerDispatchService $dispatchService
    ) {}

    public function createFromTask(CrawlerTask $task, CrawlerConfig $config, Lexicon $lexicon): void
    {
        $keywords = $lexicon->keywords()
            ->where('status', 'enabled')
            ->pluck('keywords')
            ->flatMap(fn ($keywords) => $keywords);

        foreach ($keywords as $keyword) {

            $item = CrawlerTaskItem::create([
                'task_id' => (string) $task->id,
                'keywords' => $keyword,
                'status' => 'pending',
                'crawler_machine' => 'bot-node-'.rand(1, 3),
                'error_message' => null,
            ]);

            $this->dispatchService->dispatch($item);
        }
    }

    public function updateStatus(
        string $id,
        string $status,
        ?string $resultFile = null,
        ?string $error = null
    ): void {
        CrawlerTaskItem::where('id', $id)->update([
            'status' => $status,
            'result_file' => $resultFile,
            'error_message' => $error,
        ]);
    }
}
