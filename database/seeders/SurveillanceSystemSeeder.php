<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SurveillanceSystemSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $lexiconId = Str::uuid();
            DB::table('lexicons')->insert([
                [
                    'id' => $lexiconId,
                    'name' => 'Adult Content',
                    'remark' => 'Adult keyword bank',
                    'status' => 'enabled',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);

            for ($i = 1; $i <= 3; $i++) {
                DB::table('lexicon_keywords')->insert([
                    'id' => Str::uuid(),
                    'lexicon_id' => $lexiconId,
                    'keywords' => json_encode(["keyword_$i"]),
                    'crawl_hit_count' => rand(1, 10),
                    'case_count' => rand(0, 5),
                    'status' => 'enabled',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $configId = Str::uuid();
            DB::table('crawler_configs')->insert([
                'id' => $configId,
                'name' => 'Daily Crawl',
                'sources' => json_encode(['https://example.com']),
                'lexicon_id' => $lexiconId,
                'description' => 'Daily crawling config',
                'frequency_code' => 'daily',
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $taskId = Str::uuid();
            DB::table('crawler_tasks')->insert([
                'id' => $taskId,
                'crawler_config_id' => $configId,
                'lexicon_id' => $lexiconId,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $taskItemId = Str::uuid();
            DB::table('crawler_task_items')->insert([
                'id' => $taskItemId,
                'task_id' => $taskId,
                'keywords' => 'keyword_1',
                'crawl_location' => 'https://example.com/page',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $aiModelId = Str::uuid();
            DB::table('ai_models')->insert([
                'id' => $aiModelId,
                'name' => 'Vision Model',
                'type' => 'image',
                'version' => 'v1',
                'health_status' => 'stable',
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $aiModelTaskId = Str::uuid();
            DB::table('ai_model_tasks')->insert([
                'id' => $aiModelTaskId,
                'ai_model_id' => $aiModelId,
                'crawler_task_item_id' => $taskItemId,
                'file_name' => 'result.jpg',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $predictId = Str::uuid();
            DB::table('ai_predict_results')->insert([
                'id' => $predictId,
                'ai_model_task_id' => $aiModelTaskId,
                'lexicon_id' => $lexiconId,
                'keywords' => 'keyword_1',
                'ai_score' => 88.50,
                'analysis_result' => 'Detected abnormal content',
                'review_status' => 'pending',
                'audit_status' => 'pending',
                'ai_analysis_result' => 'abnormal',
                'ai_analysis_detail' => json_encode(['confidence' => 0.88]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            /* ================= AI RESULT ITEMS ================= */

            for ($i = 1; $i <= 3; $i++) {
                DB::table('ai_predict_result_items')->insert([
                    'id' => Str::uuid(),
                    'ai_predict_result_id' => $predictId,
                    'media_url' => "https://cdn.example.com/image_$i.jpg",
                    'crawler_page_url' => 'https://example.com/page',
                    'ai_result' => 'abnormal',
                    'status' => 'valid',
                    'ai_score' => rand(70, 99),
                    'keywords' => 'keyword_1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $caseId = Str::uuid();
            DB::table('case_management')->insert([
                'id' => $caseId,
                'ai_predict_result_id' => $predictId,
                'internal_case_no' => 'INT-001',
                'external_case_no' => 'EXT-001',
                'keywords' => 'keyword_1',
                'status' => 'created',
                'comment' => 'Auto generated case',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // for ($i = 1; $i <= 3; $i++) {
            //     DB::table('case_management_items')->insert([
            //         'id' => Str::uuid(),
            //         'case_management_id' => $caseId,
            //         'media_url' => "https://cdn.example.com/case_$i.jpg",
            //         'crawler_page_url' => 'https://example.com/page',
            //         'ai_result' => 'abnormal',
            //         'status' => 'valid',
            //         'ai_score' => rand(70, 99),
            //         'keywords' => 'keyword_1',
            //         'issue_date' => Carbon::now(),
            //         'due_date' => Carbon::now()->addDays(7),
            //         'created_at' => now(),
            //         'updated_at' => now(),
            //     ]);
            // }
            for ($i = 1; $i <= 3; $i++) {
                DB::table('data_sync_records')->insert([
                    'id' => Str::uuid(),
                    'source_path' => '/local/file.zip',
                    'target_path' => '/remote/file.zip',
                    'file_name' => "file_$i.zip",
                    'file_size' => rand(1000, 5000),
                    'checksum' => md5("file_$i"),
                    'transfer_type' => 'rsync',
                    'status' => 'completed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

        });
    }
}
