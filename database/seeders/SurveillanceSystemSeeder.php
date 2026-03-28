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

            // 👉 Lexicons
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
                        'parent_id'  => null,
                        'language'   => $lang,
                        'keywords'   => json_encode(["{$lang}_keyword$l"]),
                        'status'     => 'enabled',
                        'created_at' => $lexiconDate,
                        'updated_at' => $lexiconDate,
                    ]);
                }

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

                for ($t = 1; $t <= 5; $t++) {

                    $taskId   = Str::uuid();
                    $taskDate = $this->randomDate();

                    DB::table('crawler_tasks')->insert([
                        'id'                => $taskId,
                        'crawler_config_id' => $configId,
                        'lexicon_id'        => $lexiconId,
                        'status'            => collect([
                            'pending', 'processing', 'completed', 'error',
                        ])->random(),
                        'created_at'        => $taskDate,
                        'updated_at'        => $taskDate,
                    ]);

                    // 👉 Task Items
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
                            'status'          => collect([
                                'pending', 'crawling', 'syncing', 'synced', 'error',
                            ])->random(),
                            'created_at'      => $itemDate,
                            'updated_at'      => $itemDate,
                        ]);

                        // 👉 AI Model Task
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

                        // 👉 AI Predict Result
                        $predictId = Str::uuid();

                        DB::table('ai_predict_results')->insert([
                            'id'                 => $predictId,
                            'ai_model_task_id'   => $aiTaskId,
                            'lexicon_id'         => $lexiconId,
                            'keywords'           => "keyword$i",
                            'ai_score'           => rand(10, 99),
                            'ai_analysis_result' => collect(['normal', 'abnormal'])->random(),
                            'review_status'      => 'pending',
                            'audit_status'       => 'pending',
                            'created_at'         => $itemDate,
                            'updated_at'         => $itemDate,
                        ]);

                        // 👉 Predict Result Items
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

                        // 👉 Case Management
                        $caseId = Str::uuid();

                        DB::table('case_management')->insert([
                            'id'                   => $caseId,
                            'ai_predict_result_id' => $predictId,
                            'internal_case_no'     => "INT-$i",
                            'status'               => collect([
                                'pending', 'created', 'notified',
                            ])->random(),
                            'created_at'           => $itemDate,
                            'updated_at'           => $itemDate,
                        ]);

                        DB::table('case_management_items')->insert([
                            'id'                 => Str::uuid(),
                            'case_management_id' => $caseId,
                            'media_url'          => "https://img.com/$i.jpg",
                            'ai_result'          => 'abnormal',
                            'status'             => 'valid',
                            'issue_date'         => $itemDate,
                            'due_date'           => Carbon::parse($itemDate)->addDays(7),
                            'created_at'         => $itemDate,
                            'updated_at'         => $itemDate,
                        ]);
                    }
                }
            }

            // 👉 Data Sync Records
            for ($i = 1; $i <= 5; $i++) {
                DB::table('data_sync_records')->insert([
                    'id'          => Str::uuid(),
                    'source_path' => "/source/$i",
                    'target_path' => "/target/$i",
                    'file_name'   => "file$i.zip",
                    'status'      => collect(['pending', 'completed', 'failed'])->random(),
                    'created_at'  => $this->randomDate(),
                    'updated_at'  => now(),
                ]);
            }

            // 👉 Allowed IPs
            for ($i = 1; $i <= 5; $i++) {
                DB::table('allowed_ips')->insert([
                    'id'         => Str::uuid(),
                    'ip_address' => "192.168.1.$i",
                    'status'     => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

        });
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
