<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\CrawlerConfig;
use App\Models\Lexicon;
use App\Services\CrawlerTaskService;

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

        $config = CrawlerConfig::find($this->configId);

        if (!$config) {
            return; // Stop scheduling if config is not found
        }

        $lexicon = Lexicon::find($config->lexicon_id);

        if(!$lexicon) {
            return; // Stop scheduling if lexicon is not found
        }


        // 🔥 Run your crawler logic here
        app(CrawlerTaskService::class)->createFromConfig($config, $lexicon);

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
