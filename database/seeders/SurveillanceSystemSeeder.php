<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SurveillanceSystemSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $aiModels = [];

            // AI MODELS
            for ($i = 1; $i <= 5; $i++) {
                $id = Str::uuid();

                DB::table('ai_models')->insert([
                    'id'            => $id,
                    'name'          => "AI Model $i",
                    'type'          => 'vision',
                    'version'       => 'v1.' . $i,
                    'health_status' => collect(['normal', 'busy', 'stable'])->random(),
                    'status'        => 'enabled',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                $aiModels[] = $id;
            }

            // LEXICONS
            for ($l = 1; $l <= 5; $l++) {

                $lexiconId   = Str::uuid();
                $lexiconDate = $this->randomDate();

                DB::table('lexicons')->insert([
                    'id'         => $lexiconId,
                    'name'       => "Lexicon $l",
                    'remark'     => "Remark $l",
                    'status'     => 'enabled',
                    'created_at' => $lexiconDate,
                    'updated_at' => $lexiconDate,
                ]);

                DB::table('lexicon_keywords')->insert([
                    'id'         => Str::uuid(),
                    'lexicon_id' => $lexiconId,
                    'keywords'   => json_encode(["main$l"]),
                    'status'     => 'enabled',
                    'created_at' => $lexiconDate,
                    'updated_at' => $lexiconDate,
                ]);

                foreach (['en', 'zh', 'ja'] as $lang) {
                    DB::table('lexicon_keywords')->insert([
                        'id'         => Str::uuid(),
                        'lexicon_id' => $lexiconId,
                        'language'   => $lang,
                        'keywords'   => json_encode(["{$lang}_keyword$l"]),
                        'status'     => 'enabled',
                        'created_at' => $lexiconDate,
                        'updated_at' => $lexiconDate,
                    ]);
                }

                // CRAWLER CONFIG
                $configId = Str::uuid();

                DB::table('crawler_configs')->insert([
                    'id'             => $configId,
                    'name'           => "Config $l",
                    'sources'        => json_encode(['facebook', 'twitter']),
                    'lexicon_id'     => $lexiconId,
                    'frequency_code' => collect(['daily', 'weekly', 'monthly'])->random(),
                    'status'         => 'enabled',
                    'created_at'     => $lexiconDate,
                    'updated_at'     => $lexiconDate,
                ]);

                // TASKS
                for ($t = 1; $t <= 5; $t++) {

                    $taskId   = Str::uuid();
                    $taskDate = $this->randomDate();

                    DB::table('crawler_tasks')->insert([
                        'id'                => $taskId,
                        'crawler_config_id' => $configId,
                        'lexicon_id'        => $lexiconId,
                        'status'            => collect(['pending', 'processing', 'completed', 'error'])->random(),
                        'created_at'        => $taskDate,
                        'updated_at'        => $taskDate,
                    ]);

                    // TASK ITEMS
                    for ($i = 1; $i <= 5; $i++) {

                        $itemId   = Str::uuid();
                        $itemDate = $this->randomDate();

                        DB::table('crawler_task_items')->insert([
                            'id'              => $itemId,
                            'task_id'         => $taskId,
                            'keywords'        => "keyword$i",
                            'crawler_machine' => "bot-" . rand(1, 20),
                            'result_file'     => "file$i.zip",
                            'crawl_location'  => "https://example.com/$i",
                            'status'          => collect(['pending', 'crawling', 'syncing', 'synced', 'error'])->random(),
                            'created_at'      => $itemDate,
                            'updated_at'      => $itemDate,
                        ]);

                        // AI TASK
                        $aiTaskId = Str::uuid();

                        DB::table('ai_model_tasks')->insert([
                            'id'                   => $aiTaskId,
                            'ai_model_id'          => collect($aiModels)->random(),
                            'crawler_task_item_id' => $itemId,
                            'file_name'            => "result.json",
                            'status'               => collect(['pending', 'processing', 'completed'])->random(),
                            'created_at'           => $itemDate,
                            'updated_at'           => $itemDate,
                        ]);

                        // AI RESULT
                        $predictId = Str::uuid();
                        $status    = collect(['finished', 'failed', 'pending', 'running'])->random();

                        DB::table('ai_predict_results')->insert([
                            'id'                 => $predictId,
                            'ai_model_task_id'   => $aiTaskId,
                            'lexicon_id'         => $lexiconId,
                            'keywords'           => json_encode(["keyword$i"]),
                            'ai_score'           => rand(10, 99),
                            'analysis_result'    => $this->buildAnalysisResult($status),
                            'ai_analysis_result' => $status === 'finished'
                                ? collect(['normal', 'abnormal'])->random()
                                : null,
                            'ai_analysis_detail' => $this->buildAiAnalysisDetail($status),
                            'review_status'      => 'pending',
                            'audit_status'       => 'pending',
                            'created_at'         => $itemDate,
                            'updated_at'         => $itemDate,
                        ]);

                        // RESULT ITEMS
                        DB::table('ai_predict_result_items')->insert([
                            'id'                   => Str::uuid(),
                            'ai_predict_result_id' => $predictId,
                            'media_url'            => "https://img.com/$i.jpg",
                            'crawler_page_url'     => "https://page.com/$i",
                            'ai_result'            => collect(['normal', 'abnormal'])->random(),
                            'status'               => 'valid',
                            'ai_score'             => rand(10, 99),
                            'keywords'             => json_encode(["keyword$i"]),
                            'created_at'           => $itemDate,
                            'updated_at'           => $itemDate,
                        ]);
                    }
                }
            }
        });
    }

    private function buildAnalysisResult(string $status): string
    {
        $base = [
            'status'    => $status,
            'params'    => json_encode([
                'dir_path'   => 'test_3',
                'image_type' => 'screenshot',
            ]),
            'timestamp' => now()->toDateTimeString(),
        ];

        return match ($status) {

            'finished' => json_encode([
                ...$base,
                'result' => $this->fakeAiResult(),
            ], JSON_UNESCAPED_UNICODE),

            'failed' => json_encode([
                ...$base,
                'result' => 'task/test_5.zip or task/test_5 not found',
            ], JSON_UNESCAPED_UNICODE),

            'pending', 'running' => json_encode([
                ...$base,
                'result' => '',
            ], JSON_UNESCAPED_UNICODE),

            default => json_encode($base),
        };
    }

    private function buildAiAnalysisDetail(string $status): ?string
    {
        return match ($status) {

            'finished' => json_encode([
                'victim' => [
                    [
                        'image' => 'task/test_3/275.png',
                        'victims' => [
                            [
                                'user_name' => 'victim_1',
                                'facial_area' => [
                                    'x' => 78,
                                    'y' => 32,
                                    'w' => 43,
                                    'h' => 57,
                                ],
                                'similarity' => 0.80,
                            ],
                        ],
                    ],
                ],
                'age' => [
                    [
                        'underage_probability' => 0.99,
                        'message' => 'successful',
                        'success' => true,
                        'path' => 'task/test_3/112.png',
                    ],
                ],
                'nsfw' => [
                    [
                        'image' => 'task/test_3/112.png',
                        'result' => [
                            'porn' => 0.88,
                        ],
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'failed' => json_encode([
                'error' => 'task/test_5.zip not found',
            ]),

            'pending' => json_encode([
                'message' => 'Queued',
            ]),

            'running' => json_encode([
                'message' => 'Processing',
            ]),

            default => null,
        };
    }

    private function fakeAiResult(): string
    {
        return json_encode([
            'victim' => [],
            'age'    => [],
            'nsfw'   => [],
        ]);
    }

    private function randomDate()
    {
        return Carbon::createFromTimestamp(
            rand(
                Carbon::create(2024, 1, 1)->timestamp,
                Carbon::create(2026, 12, 31)->timestamp
            )
        );
    }
}
