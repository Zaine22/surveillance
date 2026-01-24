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
        $rawKeywords = $lexicon->keywords()
            ->where('status', 'enabled')
            ->pluck('keywords')
            ->toArray();

        $keywords = collect($rawKeywords)
            ->flatMap(function ($value) {
                if (is_string($value)) {
                    $decoded = json_decode($value, true);

                    return is_array($decoded) ? $decoded : [$value];
                }

                if (is_array($value)) {
                    return $value;
                }

                return [];
            })
            ->map(fn ($k) => is_string($k) ? trim($k) : null)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $crawlLocations = collect(
            is_string($config->sources)
                ? json_decode($config->sources, true) ?? [$config->sources]
                : (is_array($config->sources) ? $config->sources : [])
        )
            ->map(fn ($url) => is_string($url) ? trim($url) : null)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        foreach ($crawlLocations as $location) {
            foreach ($keywords as $keyword) {

                $item = CrawlerTaskItem::create([
                    'task_id' => (string) $task->id,
                    'keywords' => (string) $keyword,
                    'crawl_location' => (string) $location,
                    'status' => 'pending',
                    'crawler_machine' => 'bot-node-'.rand(1, 3),
                    'error_message' => null,
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
            'status' => $status,
            'result_file' => $resultFile,
            'error_message' => $error,
        ]);
    }
}
