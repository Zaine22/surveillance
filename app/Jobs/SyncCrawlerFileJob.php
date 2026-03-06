<?php

namespace App\Jobs;

use App\Models\CrawlerTaskItem;
use App\Services\DataSyncOrchestratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncCrawlerFileJob implements ShouldQueue
{
    use Queueable;

    public function __construct( 
        public CrawlerTaskItem $item
    ) {}

    public function handle(DataSyncOrchestratorService $orchestrator): void
    {
        $orchestrator->syncCrawlerFileToNas($this->item);
    }
}
