<?php
namespace App\Http\Controllers;

use App\Models\CrawlerTaskItem;

use App\Services\AiTaskManagerService;

class AiHealthLogController extends Controller
{
    public function aiTest(AiTaskManagerService $service)
    {
        $crawlerItem = CrawlerTaskItem::find('019dbe90-c9b4-71ba-9333-09b3c1071bda');

        if (! $crawlerItem) {
            return response()->json([
                'success' => false,
                'message' => 'Crawler task item not found',
            ], 404);
        }

        $result = $service->createFromCrawlerItem($crawlerItem);

        return response()->json([
            'success'         => true,
            'message'         => 'AI task created from crawler item',
            'crawler_item_id' => $crawlerItem->id,
            'result'          => $result,
        ]);
    }

}
