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

        $keywordsJson = json_encode($keywordModel->keywords);

        $predictId = Str::uuid();

        DB::table('ai_predict_results')->insert([
            'id'                 => $predictId,
            'ai_model_task_id'   => $task->id,
            'lexicon_id'         => $lexicon->id,
            'keywords'           => $keywordsJson,
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
    }
}
