<?php

namespace App\Jobs;

use App\Models\CaseManagementItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

class ProcessExternalCaseJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 120;

    public array $backoff = [60, 180];

    public function __construct(public CaseManagementItem $caseItem) {}

    public function handle(): void
    {
        Http::post(
            config('services.screenshot.url'),
            [
                'case_management_id' => $this->caseItem->case_management_id,
                'case_management_item_id' => $this->caseItem->id,
                'url' => $this->caseItem->crawler_page_url,
            ]
        );
    }

    public function failed(Throwable $e): void
    {
        \Illuminate\Support\Facades\Log::error('ProcessExternalCaseJob failed', [
            'case_management_item_id' => $this->caseItem->id,
            'error' => $e->getMessage(),
        ]);
    }
}
