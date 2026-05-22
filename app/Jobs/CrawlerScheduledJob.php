<?php
namespace App\Jobs;

use App\Models\CrawlerConfig;
use App\Models\Lexicon;
use App\Services\CrawlerTaskService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CrawlerScheduledJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels, InteractsWithQueue;

    public string $configId;
    public string $frequency;
    public Carbon $endDate;

    public function __construct(string $configId, string $frequency, Carbon $endDate)
    {
        $this->configId  = $configId;
        $this->frequency = $frequency;
        $this->endDate   = $endDate;
    }

    public function handle()
    {
        $now = now();

        if ($now->greaterThan($this->endDate)) {
            return; // Stop scheduling
        }

        $config = CrawlerConfig::find($this->configId);

        if (! $config || $config->status === 'disabled') {
            return;
        }

        if ($config->status === 'pending') {
            $config->update([
                'status' => 'enabled',
            ]);
        }

        $lexicon = Lexicon::find($config->lexicon_id);

        if (! $lexicon) {
            return; // Stop scheduling if lexicon is not found
        }


        app(CrawlerTaskService::class)->addItemsToExistingTask($config, $lexicon);


        $nextRun = match ($this->frequency) {
            'daily'   => $now->addDay(),
            'weekly'  => $now->addWeek(),
            'monthly' => $now->addMonth(),
            default   => null,
        };

        if ($nextRun && $nextRun->lessThanOrEqualTo($this->endDate)) {
            self::dispatch($this->configId, $this->frequency, $this->endDate)
                ->delay($nextRun);
        }
    }
}
