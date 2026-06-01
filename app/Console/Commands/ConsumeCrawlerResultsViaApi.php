<?php
namespace App\Console\Commands;

use App\Services\CrawlerResultConsumeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConsumeCrawlerResultsViaApi extends Command
{
    protected $signature = 'crawler:consume-results-api';

    protected $description = 'Consume crawler result stream via API Gateway';

    public function __construct(
        protected CrawlerResultConsumeService $service
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Crawler result consumer (API) started');
        Log::info('Crawler result consumer (API) started');

        // Use the new API-based consume method
        $this->service->consumeViaApi();

        return Command::SUCCESS;
    }
}