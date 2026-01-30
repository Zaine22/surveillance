<?php

namespace App\Console\Commands;

use App\Services\CrawlerResultConsumeService;
use Illuminate\Console\Command;

class ConsumeCrawlerResults extends Command
{
    protected $signature = 'crawler:consume-results';

    protected $description = 'Consume crawler result stream';

    public function __construct(
        protected CrawlerResultConsumeService $service
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Crawler result consumer started');
        $this->service->consume();

        return Command::SUCCESS;
    }
}
