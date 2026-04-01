<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiPredictResultSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $tasks = DB::table('ai_model_tasks')->get();

            foreach ($tasks as $task) {

                $predictId = (string) Str::uuid();
                $status    = collect(['finished', 'failed', 'pending', 'running'])->random();

                $keywords = json_encode(['sample']);

                DB::table('ai_predict_results')->insert([
                    'id'                 => $predictId,
                    'ai_model_task_id'   => $task->id,
                    'lexicon_id'         => null,
                    'keywords'           => $keywords,
                    'ai_score'           => rand(50, 99),
                    'analysis_result'    => $this->buildAnalysisResult($status),
                    'ai_analysis_result' => $status === 'finished'
                        ? collect(['normal', 'abnormal'])->random()
                        : null,
                    'ai_analysis_detail' => $this->buildAiAnalysisDetail($status),
                    'review_status'      => 'pending',
                    'audit_status'       => 'pending',
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                for ($i = 1; $i <= rand(1, 3); $i++) {

                    DB::table('ai_predict_result_items')->insert([
                        'id'                   => (string) Str::uuid(),
                        'ai_predict_result_id' => $predictId,
                        'media_url'            => "https://picsum.photos/300/200",
                        'crawler_page_url'     => "https://example.com/page/{$task->id}",
                        'ai_result'  => collect(['normal', 'abnormal'])->random(),
                        'status'     => 'valid',
                        'ai_score'   => rand(50, 99),
                        'keywords'   => $keywords,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('case_management')->insert([
                    'id'                   => (string) Str::uuid(),
                    'ai_predict_result_id' => $predictId,
                    'internal_case_no'     => $this->generateInternalCaseNo(),
                    'keywords'             => $keywords,
                    'status'               => 'pending',
                    'comment'              => 'Auto generated case',
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }
        });
    }

    /**
     * Generate internal case number
     * Example: CASE-20260401-0001
     */
    private function generateInternalCaseNo(): string
    {
        return 'CASE-' . now()->format('Ymd') . '-' . rand(1000, 9999);
    }

    private function buildAnalysisResult(string $status): string
    {
        return json_encode([
            'status'    => $status,
            'params'    => [
                'dir_path'   => 'demo',
                'image_type' => 'screenshot',
            ],
            'result'    => $status === 'finished'
                ? ['nsfw' => [], 'age' => [], 'victim' => []]
                : '',
            'timestamp' => now()->toDateTimeString(),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function buildAiAnalysisDetail(string $status): ?string
    {
        if ($status !== 'finished') {
            return json_encode([
                'message' => ucfirst($status),
            ]);
        }

        return json_encode([
            'nsfw' => [
                [
                    'image'  => 'demo/image1.jpg',
                    'result' => ['porn' => rand(50, 99) / 100],
                ],
            ],
            'age'  => [
                [
                    'underage_probability' => rand(50, 99) / 100,
                    'success'              => true,
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }
}
