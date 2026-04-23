<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiPredictResultSeeder extends Seeder
{
    // public function run(): void
    // {
    //     DB::transaction(function () {

    //         $tasks = DB::table('ai_model_tasks')->get();

    //         $lexiconId = '019cd717-3c37-704d-ae0c-d61fac6d9690';

    //         $lexiconKeywords = DB::table('lexicon_keywords')
    //             ->where('lexicon_id', $lexiconId)
    //             ->pluck('keywords')
    //             ->toArray();

    //         $keywordsPool = collect($lexiconKeywords)
    //             ->flatMap(fn($item) => json_decode($item, true) ?? [])
    //             ->unique()
    //             ->values()
    //             ->toArray();

    //         if (empty($keywordsPool)) {
    //             $keywordsPool = ['default'];
    //         }

    //         foreach ($tasks as $task) {

    //             $predictId = (string) Str::uuid();

    //             $status = $this->getRandomStatus();

    //             $randomKeywords = collect($keywordsPool)
    //                 ->shuffle()
    //                 ->take(rand(1, min(3, count($keywordsPool))))
    //                 ->values()
    //                 ->toArray();

    //             $keywords = json_encode($randomKeywords);

    //             // default values
    //             $aiScore          = null;
    //             $analysisResult   = null;
    //             $aiAnalysisResult = null;
    //             $aiAnalysisDetail = null;

    //             switch ($status) {

    //                 case 'pending':
    //                     break;

    //                 case 'running':
    //                     $analysisResult = json_encode([
    //                         'status'    => 'running',
    //                         'progress'  => rand(10, 80) . '%',
    //                         'timestamp' => now()->toDateTimeString(),
    //                     ]);
    //                     break;

    //                 case 'failed':
    //                     $analysisResult = json_encode([
    //                         'status'    => 'failed',
    //                         'error'     => 'AI processing failed',
    //                         'timestamp' => now()->toDateTimeString(),
    //                     ]);

    //                     $aiAnalysisDetail = json_encode([
    //                         'message' => 'Model crashed or timeout',
    //                     ]);
    //                     break;

    //                 case 'finished':
    //                     $aiScore = rand(50, 99);

    //                     $analysisResult = $this->buildAnalysisResult($status);

    //                     $aiAnalysisResult = collect(['normal', 'abnormal'])->random();

    //                     $aiAnalysisDetail = $this->buildAiAnalysisDetail($status);
    //                     break;
    //             }

    //             DB::table('ai_predict_results')->insert([
    //                 'id'                 => $predictId,
    //                 'ai_model_task_id'   => $task->id,
    //                 'lexicon_id'         => $lexiconId,
    //                 'keywords'           => $keywords,
    //                 'ai_score'           => $aiScore,
    //                 'analysis_result'    => $analysisResult,
    //                 'ai_analysis_result' => $aiAnalysisResult,
    //                 'ai_analysis_detail' => $aiAnalysisDetail,
    //                 'review_status'      => 'pending',
    //                 'audit_status'       => 'pending',
    //                 'created_at'         => now(),
    //                 'updated_at'         => now(),
    //             ]);

    //             if ($status !== 'pending') {

    //                 for ($i = 1; $i <= rand(1, 3); $i++) {

    //                     DB::table('ai_predict_result_items')->insert([
    //                         'id'                   => (string) Str::uuid(),
    //                         'ai_predict_result_id' => $predictId,
    //                         'media_url'            => "https://picsum.photos/300/200",
    //                         'crawler_page_url'     => "https://example.com/page/{$task->id}",
    //                         'ai_result'  => $status === 'finished'
    //                             ? collect(['normal', 'abnormal'])->random()
    //                             : null,
    //                         'status'     => $status === 'failed' ? 'invalid' : 'valid',
    //                         'ai_score'   => $status === 'finished' ? rand(50, 99) : null,
    //                         'keywords'   => $keywords,
    //                         'created_at' => now(),
    //                         'updated_at' => now(),
    //                     ]);
    //                 }
    //             }
    //             DB::table('case_management')->insert([
    //                 'id'                   => (string) Str::uuid(),
    //                 'ai_predict_result_id' => $predictId,
    //                 'internal_case_no'     => $this->generateInternalCaseNo(),
    //                 'keywords'             => $keywords,
    //                 'status'               => 'pending_notification',
    //                 'comment'              => 'Auto generated case (abnormal)',
    //                 'created_at'           => now(),
    //                 'updated_at'           => now(),
    //             ]);
    //         }
    //     });
    // }

    public function run(): void
    {
        DB::transaction(function () {

            $tasks = DB::table('ai_model_tasks')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('ai_predict_results')
                        ->whereColumn('ai_predict_results.ai_model_task_id', 'ai_model_tasks.id');
                })
                ->get();

            foreach ($tasks as $task) {

                $item = DB::table('crawler_task_items')
                    ->where('id', $task->crawler_task_item_id)
                    ->first();

                if (! $item) {
                    continue;
                }

                $crawlerTask = DB::table('crawler_tasks')
                    ->where('id', $item->task_id)
                    ->first();

                if (! $crawlerTask) {
                    continue;
                }

                $lexiconId = $crawlerTask->lexicon_id;

                $lexiconKeywords = DB::table('lexicon_keywords')
                    ->where('lexicon_id', $lexiconId)
                    ->pluck('keywords')
                    ->toArray();

                $keywordsPool = collect($lexiconKeywords)
                    ->flatMap(fn($item) => json_decode($item, true) ?? [])
                    ->unique()
                    ->values()
                    ->toArray();

                if (empty($keywordsPool)) {
                    $keywordsPool = ['default'];
                }

                $predictId = (string) Str::uuid();
                $status    = $this->getRandomStatus();

                $randomKeywords = collect($keywordsPool)
                    ->shuffle()
                    ->take(rand(1, min(3, count($keywordsPool))))
                    ->values()
                    ->toArray();

                $keywords = json_encode($randomKeywords);

                $aiScore          = null;
                $analysisResult   = null;
                $aiAnalysisResult = null;
                $aiAnalysisDetail = null;

                switch ($status) {
                    case 'pending':
                        break;

                    case 'running':
                        $analysisResult = json_encode([
                            'status'    => 'running',
                            'progress'  => rand(10, 80) . '%',
                            'timestamp' => now()->toDateTimeString(),
                        ]);
                        break;

                    case 'failed':
                        $analysisResult = json_encode([
                            'status'    => 'failed',
                            'error'     => 'AI processing failed',
                            'timestamp' => now()->toDateTimeString(),
                        ]);
                        $aiAnalysisDetail = json_encode([
                            'message' => 'Model crashed or timeout',
                        ]);
                        break;

                    case 'finished':
                        $aiScore          = rand(50, 99);
                        $analysisResult   = $this->buildAnalysisResult($status);
                        $aiAnalysisResult = collect(['normal', 'abnormal'])->random();
                        $aiAnalysisDetail = $this->buildAiAnalysisDetail($status);
                        break;
                }

                DB::table('ai_predict_results')->insert([
                    'id'                 => $predictId,
                    'ai_model_task_id'   => $task->id,
                    'lexicon_id'         => $lexiconId,
                    'keywords'           => $keywords,
                    'ai_score'           => $aiScore,
                    'analysis_result'    => $analysisResult,
                    'ai_analysis_result' => $aiAnalysisResult,
                    'ai_analysis_detail' => $aiAnalysisDetail,
                    'review_status'      => 'pending',
                    'audit_status'       => 'pending',
                    'created_at'         => $task->created_at,
                    'updated_at'         => now(),
                ]);

                if ($status !== 'pending') {
                    DB::table('ai_predict_result_items')->insert([
                        'id'                   => (string) Str::uuid(),
                        'ai_predict_result_id' => $predictId,
                        'media_url'            => $item->media_url ?? "https://picsum.photos/300/200",
                        'crawler_page_url'     => $item->page_url ?? null,
                        'ai_result'            => $status === 'finished'
                            ? collect(['normal', 'abnormal'])->random()
                            : null,
                        'status'               => $status === 'failed' ? 'invalid' : 'valid',
                        'ai_score'             => $status === 'finished' ? rand(50, 99) : null,
                        'keywords'             => $keywords,
                        'created_at'           => $task->created_at,
                        'updated_at'           => now(),
                    ]);
                }

                if ($aiAnalysisResult === 'abnormal') {
                    DB::table('case_management')->insert([
                        'id'                   => (string) Str::uuid(),
                        'ai_predict_result_id' => $predictId,
                        'internal_case_no'     => $this->generateInternalCaseNo(),
                        'keywords'             => $keywords,
                        'status'               => 'pending_notification',
                        'comment'              => 'Auto generated case (abnormal)',
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ]);
                }
            }
        });
    }

    private function getRandomStatus(): string
    {
        $rand = rand(1, 100);

        if ($rand <= 65) {
            return 'finished';
        }

        if ($rand <= 80) {
            return 'failed';
        }

        if ($rand <= 95) {
            return 'running';
        }

        return 'pending';
    }

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
            'result'    => [
                'nsfw'   => [],
                'age'    => [],
                'victim' => [],
            ],
            'timestamp' => now()->toDateTimeString(),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function buildAiAnalysisDetail(string $status): string
    {
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
