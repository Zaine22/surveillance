<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        foreach ($request->urls as $url) {
            $taskItemId = Str::uuid();
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
                'status' => 'synced',
                'error_message' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $responseItems[] = [
                'task_item_id' => (string) $taskItemId,
                'url' => $url,
            ];
        }

        DB::table('crawler_task_items')->insert($rows);

        return response()->json([
            'message' => 'Crawler task items stored',
            'task_id' => $task->id,
            'inserted' => count($rows),
            'items' => $responseItems,
        ]);
    }

    public function upload(Request $request)
{
    $request->validate([
        'task_item_id' => 'required|uuid|exists:crawler_task_items,id',
        'zip'          => 'required|file|mimetypes:application/zip,application/x-zip-compressed|max:51200',
    ]);

    $taskItem = DB::table('crawler_task_items')
        ->where('id', $request->task_item_id)
        ->first();

    $path = $request->file('zip')->store(
        'crawler_zips/' . date('Y/m/d').'/'.$taskItem->id,
        'public'
    );

    DB::table('crawler_task_items')
        ->where('id', $taskItem->id)
        ->update([
            'result_file' => $path,
            'status'      => 'synced',
            'updated_at'  => now(),
        ]);

    return response()->json([
        'message'      => 'ZIP file uploaded successfully',
        'task_item_id' => $taskItem->id,
        'url'          => $taskItem->url,
        'zip_file'     => $path,
    ]);
}
}
