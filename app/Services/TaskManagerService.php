<?php
namespace App\Services;

use App\Models\CrawlerTaskItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskManagerService
{
    public function __construct(
        protected DataSyncOrchestratorService $syncService,
        protected AiTaskManagerService $aiTaskManagerService
    ) {}

    // public function crawlerCompleted(string $itemId, string $filePath, string $crawlerMachine): void
    // {
    //     DB::transaction(function () use ($itemId, $filePath, $crawlerMachine) {

    //         $item = CrawlerTaskItem::lockForUpdate()->findOrFail($itemId);

    //         $item->update([
    //             'status'          => 'syncing',
    //             'result_file'     => $filePath,
    //             'crawler_machine' => $crawlerMachine,
    //         ]);

    //         $nasPath = $this->syncService->syncCrawlerFileToNas($item);

    //         $item->update([
    //             'status'      => 'synced',
    //             'result_file' => $nasPath,
    //         ]);

    //         $this->aiTaskManagerService->createFromCrawlerItem($item);
    //     });
    // }

    public function crawlerCompleted(
        string $itemId,
        string $filePath,
        string $crawlerMachine
    ): void {

        DB::transaction(function () use ($itemId, $filePath, $crawlerMachine) {

            $item = CrawlerTaskItem::lockForUpdate()->findOrFail($itemId);

            if (in_array($item->status, ['synced'])) {
                return;
            }

            $task = $item->task()->lockForUpdate()->first();

            if ($task->status === 'deleted') {
                return;
            }
            $item->update([
                'status'          => 'syncing',
                'result_file'     => $filePath,
                'crawler_machine' => $crawlerMachine,
            ]);

        });
    }

    public function crawlerFailed(string $itemId, ?string $error, ?string $crawlerMachine): void
    {
        Log::info('Crawler failed', [
            'item_id'         => $itemId,
            'error'           => $error,
            'crawler_machine' => $crawlerMachine,
        ]);
        CrawlerTaskItem::where('id', $itemId)
            ->update([
                'status'          => 'error',
                'error_message'   => $error,
                'crawler_machine' => $crawlerMachine,
            ]);
    }
}
