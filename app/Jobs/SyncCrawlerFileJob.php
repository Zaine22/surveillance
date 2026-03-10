<?php
namespace App\Jobs;

use App\Models\CrawlerTaskItem;
use App\Services\AiTaskManagerService;
use App\Services\DataSyncOrchestratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncCrawlerFileJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CrawlerTaskItem $item,
    ) {}

    public function handle(DataSyncOrchestratorService $orchestrator): void
    {
        \Illuminate\Support\Facades\Log::info('SyncCrawlerFileJob now handling file sync', [
            'item_id'     => $this->item->id,
            'result_file' => $this->item->result_file,
        ]);
        $orchestrator->syncCrawlerFileToNas($this->item);
       

    }
}
