<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CrawlerScheduledJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    protected string $configId;
    protected string $frequency;
    protected Carbon $endDate;

    public function __construct(string $configId, string $frequency, Carbon $endDate)
    {
        $this->configId = $configId;
        $this->frequency = $frequency;
        $this->endDate = $endDate;
    }

    public function handle()
    {
        $now = now();

        if ($now->greaterThan($this->endDate)) {
            return; // Stop scheduling
        }

        // 🔥 Run your crawler logic here
        app(CrawlerTaskService::class)->run($this->configId);

        // 📅 Schedule next run
        $nextRun = match ($this->frequency) {
            'daily'  => $now->addDay(),
            'weekly' => $now->addWeek(),
            default  => null,
        };

        if ($nextRun && $nextRun->lessThanOrEqualTo($this->endDate)) {
            self::dispatch($this->configId, $this->frequency, $this->endDate)
                ->delay($nextRun);
        }
    }
}
