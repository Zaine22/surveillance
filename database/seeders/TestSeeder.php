<?php

namespace Database\Seeders;

use App\Models\AiModelTask;
use App\Models\Lexicon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        $task    = AiModelTask::firstOrFail();
        $lexicon = Lexicon::with('keywords')->firstOrFail();

        $keywordModel = $lexicon->keywords()->firstOrFail();

        // Convert array → string (because column is VARCHAR)
        $keywordsString = implode(',', $keywordModel->keywords);

        $predictId = Str::uuid();

        // Insert ai_predict_results
        DB::table('ai_predict_results')->insert([
            'id'                 => $predictId,
            'ai_model_task_id'   => $task->id,
            'lexicon_id'         => $lexicon->id,
            'keywords'           => $keywordsString,
            'ai_score'           => 88.50,
            'analysis_result'    => 'Detected abnormal content',
            'review_status'      => 'pending',
            'audit_status'       => 'pending',
            'ai_analysis_result' => 'abnormal',
            'ai_analysis_detail' => json_encode(['confidence' => 0.88]),
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        // Insert ai_predict_result_items
        for ($i = 1; $i <= 3; $i++) {
            DB::table('ai_predict_result_items')->insert([
                'id'                   => Str::uuid(),
                'ai_predict_result_id' => $predictId,
                'media_url'            => "https://picsum.photos/id/237/200/300",
                'crawler_page_url'     => $task->crawlerTaskItem->crawl_location,
                'ai_result'            => 'abnormal',
                'status'               => 'valid',
                'ai_score'             => rand(70, 99),
                'keywords'             => $keywordsString, // FIXED
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }

        // Insert case_management
        DB::table('case_management')->insert([
            'id'                   => Str::uuid(),
            'ai_predict_result_id' => $predictId,
            'internal_case_no'     => 'INT-001',
            'keywords'             => $keywordsString,
            'status'               => 'created',
            'comment'              => 'Auto generated case',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }
}
