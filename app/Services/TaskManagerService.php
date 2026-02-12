<?php

namespace App\Services;

use App\Models\CrawlerTaskItem;
use App\Services\AiTaskManagerService;
use App\Services\DataSyncOrchestratorService;
use Illuminate\Support\Facades\DB;

class TaskManagerService
{
    public function __construct(
        protected DataSyncOrchestratorService $syncService,
        protected AiTaskManagerService $aiTaskManagerService
    ) {}

    public function crawlerCompleted(string $itemId, string $filePath, string $crawler_machine): void
    {
        DB::transaction(function () use ($itemId, $filePath, $crawler_machine) {

            $item = CrawlerTaskItem::lockForUpdate()->findOrFail($itemId);

            $item->update([
                'status' => 'syncing',
                'result_file' => $filePath,
                'crawler_machine' => $crawler_machine,
            ]);

            $nasPath = $this->syncService->syncCrawlerFileToNas($item);

            $item->update([
                'status' => 'synced',
                'result_file' => $nasPath,

            ]);

            $this->aiTaskManagerService->createFromCrawlerItem($item);
        });
    }

    public function crawlerFailed(string $itemId, ?string $error): void
    {
        CrawlerTaskItem::where('id', $itemId)
            ->update(['status' => 'error', 'error_message' => $error]);
    }
}
