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
                'message' => '找不到爬蟲任務項目。',
            ], 404);
        }

        $result = $service->createFromCrawlerItem($crawlerItem);

        return response()->json([
            'success'         => true,
            'message'         => '從爬蟲項目建立的 AI 任務',
            'crawler_item_id' => $crawlerItem->id,
            'result'          => $result,
        ]);
    }

}
