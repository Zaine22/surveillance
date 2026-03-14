<?php
namespace Database\Seeders;

use App\Models\AiModelTask;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        $task = AiModelTask::with('crawlerTaskItem.task.lexicon.keywords')->firstOrFail();

        $lexicon = $task->crawlerTaskItem->task->lexicon;

        $keywordsArray = $lexicon->keywords
            ->pluck('keywords')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        $keywordsString = substr(implode(',', $keywordsArray), 0, 250); // for varchar columns
        $keywordsJson   = json_encode($keywordsArray);                  // for json columns

        $predictId = Str::uuid();

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

        for ($i = 1; $i <= 3; $i++) {
            DB::table('ai_predict_result_items')->insert([
                'id'                   => Str::uuid(),
                'ai_predict_result_id' => $predictId,
                'media_url'            => "https://picsum.photos/id/237/200/300",
                'crawler_page_url'     => $task->crawlerTaskItem->crawl_location,
                'ai_result'            => 'abnormal',
                'status'               => 'valid',
                'ai_score'             => rand(70, 99),
                'keywords'             => $keywordsJson,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }

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
