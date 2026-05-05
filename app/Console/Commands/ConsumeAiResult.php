<?php
namespace App\Console\Commands;

use App\Services\AiResultConsumeService;
use Illuminate\Console\Command;

class ConsumeAiResult extends Command
{
    protected $signature   = 'ai:consume-results';
    protected $description = 'Consume AI result stream from Redis';

    public function __construct(
        protected AiResultConsumeService $service
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('AI Result Consumer Started...');

        $this->service->consume();

        return Command::SUCCESS;
    }
}
