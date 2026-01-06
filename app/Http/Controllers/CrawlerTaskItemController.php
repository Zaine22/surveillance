<?php

namespace App\Http\Controllers;

use App\Jobs\CrawlTaskItemJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CrawlerTaskItemController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'urls' => 'required|array|min:1|max:100',
            'urls.*' => 'required|string|max:2000',
        ]);

        $task = DB::table('crawler_tasks')->first();

        if (! $task) {
            return response()->json([
                'error' => 'No crawler task found',
            ], 422);
        }

        $now = now();
        $rows = [];
        $responseItems = [];
        $jobIds = [];

        foreach ($request->urls as $url) {
            $taskItemId = (string) Str::uuid();

            $rows[] = [
                'id' => $taskItemId,
                'task_id' => $task->id,
                'keywords' => collect([
                    'underage',
                    'nudity',
                    'explicit',
                    '诱拐',
                    '未成年',
                ])->random(),
                'url' => $url,
                'crawler_machine' => 'bot-node-'.rand(1, 3),
                'result_file' => null,
                'status' => 'pending',
                'error_message' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $jobIds[] = $taskItemId;

            $responseItems[] = [
                'task_item_id' => $taskItemId,
                'url' => $url,
            ];
        }

        DB::table('crawler_task_items')->insert($rows);

        foreach ($jobIds as $taskItemId) {
            CrawlTaskItemJob::dispatch($taskItemId);
        }

        return response()->json([
            'message' => 'Crawler task items stored',
            'task_id' => $task->id,
            'items' => $responseItems,
            'inserted' => count($rows),
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'task_item_id' => 'required|uuid|exists:crawler_task_items,id',
            'result_file' => 'required|string|max:2000',
        ]);

        $taskItem = DB::table('crawler_task_items')
            ->where('id', $request->task_item_id)
            ->first();

        DB::table('crawler_task_items')
            ->where('id', $taskItem->id)
            ->update([
                'result_file' => $request->result_file,
                'status' => 'synced',
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'ZIP file uploaded successfully',
            'task_item_id' => $taskItem->id,
            'url' => $taskItem->url,
            'zip_file' => $request->result_file,
        ]);
    }

    public function trigger(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|max:100',
        ]);
        Http::timeout(5)->post(
            config('services.python.url').'/api/crawler/crawl',
            [
                'keyword' => $request->keyword,
            ]
        );

        return response()->json([
            'message' => 'Crawler triggered',
            'keyword' => $request->keyword,
        ]);
    }
}
