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

            // Idempotency protection
            if (in_array($item->status, ['synced'])) {
                return;
            }

            $task = $item->task()->lockForUpdate()->first();

            // If task deleted → ignore
            if ($task->status === 'deleted') {
                return;
            }

            //Update item to syncing
            $item->update([
                'status'          => 'syncing',
                'result_file'     => $filePath,
                'crawler_machine' => $crawlerMachine,
            ]);

            // Sync file (side effect)
            $nasPath = $this->syncService->syncCrawlerFileToNas($item);

            // Finalize item
            $item->update([
                'status'      => 'synced',
                'result_file' => $nasPath,
            ]);

            // Create AI task
            $this->aiTaskManagerService->createFromCrawlerItem($item);

            // Auto-complete ONLY if processing
            if (
                $task->status === 'processing' &&
                $task->items()
                ->whereNotIn('status', ['synced', 'error'])
                ->count() === 0
            ) {
                $task->update(['status' => 'completed']);
            }
        });
    }

    public function crawlerFailed(string $itemId, ?string $error, ?string $crawlerMachine): void
    {
        CrawlerTaskItem::where('id', $itemId)
            ->update([
                'status'          => 'error',
                'error_message'   => $error,
                'crawler_machine' => $crawlerMachine,
            ]);
    }
}
