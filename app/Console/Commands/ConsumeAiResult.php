<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AiResultConsumeService;

class ConsumeAiResult extends Command
{
    protected $signature = 'ai:consume-results';
    protected $description = 'Consume AI result stream from Redis';

    public function handle(AiResultConsumeService $service): int
    {
        $this->info('AI Result Consumer Started...');

        $service->consume();

        return Command::SUCCESS;
    }
}
