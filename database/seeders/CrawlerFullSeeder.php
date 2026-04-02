<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CrawlerFullSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $lexicons = DB::table('lexicons')->pluck('id')->toArray();
        $aiModels = DB::table('ai_models')->pluck('id')->toArray();

        $taskStatuses = ['pending', 'processing', 'completed', 'error', 'paused'];
        $itemStatuses = ['pending', 'crawling', 'syncing', 'synced', 'error'];
        $aiStatuses   = ['pending', 'processing', 'completed'];

        $years = [2024, 2025, 2026];

        foreach ($years as $year) {

            // 👉 create 5 configs per year
            for ($i = 1; $i <= 5; $i++) {

                $configId = (string) Str::uuid();
                $lexiconId = $lexicons[array_rand($lexicons)];

                $from = Carbon::create($year, rand(1, 6), rand(1, 28));
                $to   = (clone $from)->addMonths(rand(1, 6));

                DB::table('crawler_configs')->insert([
                    'id'             => $configId,
                    'name'           => "Crawler Config {$year}-{$i}",
                    'sources'        => json_encode(['google.com', 'facebook.com', 'news.site']),
                    'lexicon_id'     => $lexiconId,
                    'description'    => "Seeder config {$year}",
                    'frequency_code' => ['daily', 'weekly', 'monthly'][array_rand(['daily', 'weekly', 'monthly'])],
                    'status'         => rand(0, 1) ? 'enabled' : 'disabled',
                    'from'           => $from,
                    'to'             => $to,
                    'created_at'     => $from,
                    'updated_at'     => $from,
                ]);

                // 👉 tasks per config
                for ($t = 1; $t <= 3; $t++) {

                    $taskId = (string) Str::uuid();
                    $taskStatus = $taskStatuses[array_rand($taskStatuses)];

                    $taskDate = (clone $from)->addDays(rand(1, 20));

                    DB::table('crawler_tasks')->insert([
                        'id'                => $taskId,
                        'crawler_config_id' => $configId,
                        'lexicon_id'        => $lexiconId,
                        'status'            => $taskStatus,
                        'created_at'        => $taskDate,
                        'updated_at'        => $taskDate,
                    ]);

                    // 👉 task items
                    for ($k = 1; $k <= 4; $k++) {

                        $itemId = (string) Str::uuid();
                        $itemStatus = $itemStatuses[array_rand($itemStatuses)];

                        $keywords = ['Lion', 'Forest', 'Animal', 'Test', 'AI'];

                        $itemDate = (clone $taskDate)->addHours(rand(1, 48));

                        DB::table('crawler_task_items')->insert([
                            'id'               => $itemId,
                            'task_id'          => $taskId,
                            'keywords'         => json_encode($keywords),
                            'crawler_machine'  => 'bot-' . rand(1, 20),
                            'result_file'      => $itemStatus === 'synced'
                                ? "{$itemId}.zip"
                                : null,
                            'crawl_location'   => 'https://example.com/page/' . rand(1, 100),
                            'status'           => $itemStatus,
                            'error_message'    => $itemStatus === 'error'
                                ? 'Crawler timeout error'
                                : null,
                            'created_at'       => $itemDate,
                            'updated_at'       => $itemDate,
                        ]);

                        // 👉 AI MODEL TASK (only for synced / syncing)
                        if (in_array($itemStatus, ['syncing', 'synced'])) {

                            $aiTaskId = (string) Str::uuid();
                            $aiModelId = $aiModels[array_rand($aiModels)];

                            DB::table('ai_model_tasks')->insert([
                                'id'                   => $aiTaskId,
                                'ai_model_id'          => $aiModelId,
                                'crawler_task_item_id' => $itemId,
                                'file_name'            => "{$itemId}.zip",
                                'status'               => $aiStatuses[array_rand($aiStatuses)],
                                'created_at'           => $itemDate,
                                'updated_at'           => $itemDate,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
