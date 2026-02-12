<?php

namespace App\Services;

use App\Models\CrawlerConfig;
use App\Models\CrawlerTask;
use App\Models\Lexicon;
use App\Services\CrawlerTaskItemService;
use Illuminate\Support\Facades\DB;

class CrawlerTaskService
{
    public function __construct(
        protected CrawlerTaskItemService $itemService
    ) {}

    public function createFromConfig(CrawlerConfig $config, Lexicon $lexicon): CrawlerTask
    {
        return DB::transaction(function () use ($config, $lexicon) {

            $task = CrawlerTask::create([
                'crawler_config_id' => $config->id,
                'lexicon_id' => $lexicon->id,
                'status' => 'pending',
            ]);

            $this->itemService->createFromTask($task, $config, $lexicon);

            return $task;
        });
    }

    public function refreshStatus(CrawlerTask $task): void
    {
        if ($task->items()->where('status', 'error')->exists()) {
            $task->update(['status' => 'error']);

            return;
        }

        if ($task->items()->whereNotIn('status', ['synced'])->exists()) {
            $task->update(['status' => 'processing']);

            return;
        }

        $task->update(['status' => 'completed']);
    }
}
