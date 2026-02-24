<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CrawlerTaskItem;
use App\Services\CrawlerTaskItemService;

class CrawlerTaskItemController extends Controller
{
    public function __construct(
        protected CrawlerTaskItemService $crawlerTaskItemService
    ) {}

    public function retry(CrawlerTaskItem $item)
    {
        return $this->crawlerTaskItemService->retry($item);
    }

    public function delete(CrawlerTaskItem $item)
    {
        return $this->crawlerTaskItemService->delete($item);
    }
    // public function store(Request $request)
    // {

    //     $request->validate([
    //         'urls'   => 'required|array|min:1|max:100',
    //         'urls.*' => 'required|string|max:2000',
    //     ]);

    //     $task = DB::table('crawler_tasks')->first();
    //     if (! $task) {
    //         return response()->json([
    //             'error' => 'No crawler task found',
    //         ], 422);
    //     }

    //     $now           = now();
    //     $responseItems = [];

    //     foreach ($request->urls as $url) {

    //         $taskItemId = (string) \Illuminate\Support\Str::uuid();

    //         DB::table('crawler_task_items')->insert([
    //             'id'              => $taskItemId,
    //             'task_id'         => $task->id,
    //             'keywords'        => collect([
    //                 'underage',
    //                 'nudity',
    //                 'explicit',
    //                 '诱拐',
    //                 '未成年',
    //             ])->random(),
    //             'url'             => $url,
    //             'crawler_machine' => 'bot-node-' . rand(1, 3),
    //             'result_file'     => null,
    //             'status'          => 'pending',
    //             'error_message'   => null,
    //             'created_at'      => $now,
    //             'updated_at'      => $now,
    //         ]);

    //         try {
    //             $response = Http::timeout(2)
    //                 ->retry(0, 0)
    //                 ->asJson()
    //                 ->post(
    //                     config('services.python.url') . '/api/crawler/crawl/direct',
    //                     [
    //                         'task_item_id' => $taskItemId,
    //                         'url'          => $url,
    //                     ]
    //                 );

    //             if (! $response->successful()) {
    //                 throw new \Exception($response->body());
    //             }

    //         } catch (\Throwable $e) {

    //             DB::table('crawler_task_items')
    //                 ->where('id', $taskItemId)
    //                 ->update([
    //                     'status'        => 'failed',
    //                     'error_message' => substr($e->getMessage(), 0, 2000),
    //                     'updated_at'    => now(),
    //                 ]);
    //         }

    //         $responseItems[] = [
    //             'task_item_id' => $taskItemId,
    //             'url'          => $url,
    //         ];
    //     }

    //     return response()->json([
    //         'message' => 'Crawler task items stored and dispatched immediately',
    //         'task_id' => $task->id,
    //         'items'   => $responseItems,
    //         'count'   => count($responseItems),
    //     ]);
    // }

    // public function upload(Request $request)
    // {
    //     $request->validate([
    //         'task_item_id' => 'required|uuid|exists:crawler_task_items,id',
    //         'result_file'  => 'required|string|max:2000',
    //     ]);

    //     $taskItem = DB::table('crawler_task_items')
    //         ->where('id', $request->task_item_id)
    //         ->first();

    //     DB::table('crawler_task_items')
    //         ->where('id', $taskItem->id)
    //         ->update([
    //             'result_file' => $request->result_file,
    //             'status'      => 'synced',
    //             'updated_at'  => now(),
    //         ]);

    //     return response()->json([
    //         'message'      => 'ZIP file uploaded successfully',
    //         'task_item_id' => $taskItem->id,
    //         'url'          => $taskItem->url,
    //         'zip_file'     => $request->result_file,
    //     ]);
    // }

    // public function trigger(Request $request)
    // {
    //     $request->validate([
    //         'keyword' => 'required|string|max:100',
    //     ]);
    //     Http::timeout(5)->post(
    //         config('services.python.url') . '/api/crawler/crawl',
    //         [
    //             'keyword' => $request->keyword,
    //         ]
    //     );

    //     return response()->json([
    //         'message' => 'Crawler triggered',
    //         'keyword' => $request->keyword,
    //     ]);
    // }

    // public function results()
    // {
    //     $items = DB::table('crawler_task_items')
    //         ->orderBy('created_at', 'desc')
    //         ->select([
    //             'id',
    //             'url',
    //             'result_file',
    //             'status',
    //             'error_message',
    //             'created_at',
    //             'updated_at',
    //         ])
    //         ->limit(100)
    //         ->get();

    //     return response()->json([
    //         'items' => $items,
    //     ]);
    // }
}