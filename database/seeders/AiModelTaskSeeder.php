<?php
namespace Database\Seeders;

use App\Models\CrawlerTaskItem;
use App\Services\AiTaskManagerService;
use Illuminate\Database\Seeder;

class AiModelTaskSeeder extends Seeder
{
    protected $service;

    public function __construct(AiTaskManagerService $service)
    {
        $this->service = $service;
    }

    public function run(): void
    {
        $items = CrawlerTaskItem::whereDoesntHave('aiModelTask')->get();

        foreach ($items as $item) {
            $this->service->createFromCrawlerItem($item);
        }
    }
}
