<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrawlerTaskItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = DB::table('crawler_tasks')->get();

        foreach ($tasks as $task) {
            for ($i = 1; $i <= 5; $i++) {
                DB::table('crawler_task_items')->insert([
                    'id' => Str::uuid(),
                    'task_id' => $task->id,
                    'keywords' => collect([
                        'underage',
                        'nudity',
                        'explicit',
                        '诱拐',
                        '未成年',
                    ])->random(),
                    'url' => fake()->url(),
                    'crawler_machine' => 'bot-node-'.rand(1, 3),
                    'result_file' => "results/task_{$task->id}_{$i}.zip",
                    'status' => 'synced',
                    'error_message' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
