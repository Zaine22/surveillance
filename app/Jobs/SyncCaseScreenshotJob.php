<?php

namespace App\Jobs;

use App\Models\CaseManagementItem;
use App\Services\DataSyncOrchestratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncCaseScreenshotJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CaseManagementItem $item,
    ) {}

    public function handle(DataSyncOrchestratorService $orchestrator): void
    {
        Log::info('SyncCaseScreenshotJob now handling screenshot sync', [
            'item_id'   => $this->item->id,
            'media_url' => $this->item->media_url,
        ]);
        
        $orchestrator->syncCaseScreenshotToNasWithHttp($this->item);
    }
}
