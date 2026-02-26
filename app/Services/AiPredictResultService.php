<?php
namespace App\Services;

use App\Models\AiModelTask;
use App\Models\AiPredictResult;
use App\Models\AiPredictResultItem;
use App\Models\CaseManagementItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiPredictResultService extends BaseFilterService
{
    public function __construct()
    {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = AiPredictResult::with('aiModelTask');

        if (! empty($filters['search'])) {
            $search = strtolower($filters['search']);

            $query->where(function ($q) use ($search) {

                $q->orWhereRaw(
                    "LOWER(analysis_result) LIKE ?",
                    ["%{$search}%"]
                )
                    ->orWhereHas('aiModelTask', function ($task) use ($search) {
                        $task->whereRaw(
                            "LOWER(file_name) LIKE ?",
                            ["%{$search}%"]
                        );
                    });
            });
        }

        if (! empty($filters['review_status'])) {
            $query->where('review_status', $filters['review_status']);
        }

        if (! empty($filters['audit_status'])) {
            $query->where('audit_status', $filters['audit_status']);
        }

        return $this->applyFilters(
            $query,
            $filters,
            [],
            false,
            'created_at'
        );
    }
    public function findById(string $id): AiPredictResult
    {
        return AiPredictResult::query()
            ->with([
                'items',
                'caseManagement.items',
            ])
            ->findOrFail($id);
    }

    public function getResultItems(AiPredictResult $result)
    {
        return $result->items()->get();
    }

    protected function getAllowedSortColumns(): array
    {
        return [
            'created_at',
            'ai_score',
            'review_status',
            'audit_status',
            'ai_analysis_result',
        ];
    }
    // public function saveFromAiCallback(AiModelTask $task, array $payload): AiPredictResult
    // {
    //     return DB::transaction(function () use ($task, $payload) {

    //         $result = AiPredictResult::create([
    //             'id' => (string) Str::uuid(),
    //             'ai_model_task_id' => $task->id,
    //             'ai_score' => $payload['ai_score'] ?? null,
    //             'ai_analysis_result' => $payload['ai_analysis_result'] ?? null,
    //             'review_status' => 'pending',
    //             'audit_status' => 'pending',
    //             'keywords' => $payload['keywords'] ?? null,
    //         ]);

    //         foreach ($payload['items'] ?? [] as $item) {
    //             AiPredictResultItem::create([
    //                 'id' => (string) Str::uuid(),
    //                 'ai_predict_result_id' => $result->id,
    //                 'media_url' => $item['media_url'] ?? null,
    //                 'crawler_page_url' => $item['crawler_page_url'] ?? null,
    //                 'ai_result' => $item['ai_result'] ?? null,
    //                 'status' => 'valid',
    //             ]);
    //         }

    //         return $result;
    //     });
    // }

    public function saveFromAiCallback(AiModelTask $task, array $payload): AiPredictResult
    {
        return DB::transaction(function () use ($task, $payload) {

            $existing = AiPredictResult::where(
                'ai_model_task_id',
                $task->id
            )->first();

            if ($existing) {
                return $existing;
            }

            $victims = $payload['victim'] ?? [];
            $ages    = $payload['age'] ?? [];
            $nsfws   = $payload['nsfw'] ?? [];

            $hasVictim = ! empty($victims);

            $maxAgeScore  = 0.0;
            $maxPornScore = 0.0;

            foreach ($ages as $age) {
                $score = (float) ($age['result'] ?? 0);
                if ($score > $maxAgeScore) {
                    $maxAgeScore = $score;
                }
            }

            foreach ($nsfws as $nsfw) {
                $porn = (float) (($nsfw['result']['porn'] ?? 0));
                if ($porn > $maxPornScore) {
                    $maxPornScore = $porn;
                }
            }

            $aiAnalysisResult = 'normal';
            $aiScore          = max($maxAgeScore, $maxPornScore);

            if ($hasVictim) {
                $aiAnalysisResult = 'abnormal';
                $aiScore          = 1.0;
            } elseif ($maxAgeScore >= 0.7 || $maxPornScore >= 0.6) {
                $aiAnalysisResult = 'abnormal';
            }

            $result = AiPredictResult::create([
                'id'                 => (string) Str::uuid(),
                'ai_model_task_id'   => $task->id,
                'ai_score'           => $aiScore,
                'analysis_result'    => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'ai_analysis_result' => $aiAnalysisResult,
                'ai_analysis_detail' => $payload,
                'review_status'      => 'pending',
                'audit_status'       => 'pending',
                'keywords'           => $task->crawlerTaskItem->keywords ?? null,
            ]);

            foreach ($victims as $victim) {

                AiPredictResultItem::create([
                    'id'                   => (string) Str::uuid(),
                    'ai_predict_result_id' => $result->id,
                    'media_url'            => $victim['image'] ?? null,
                    'crawler_page_url'     => $task->crawlerTaskItem->crawl_location ?? null,
                    'ai_result'            => 'abnormal',
                    'status'               => 'valid',
                    'reason'               => 'victim_detected',
                    'ai_score'             => 1.0,
                    'keywords'             => $result->keywords,
                ]);
            }

            foreach ($ages as $age) {

                $score = (float) ($age['result'] ?? 0);

                AiPredictResultItem::create([
                    'id'                   => (string) Str::uuid(),
                    'ai_predict_result_id' => $result->id,
                    'media_url'            => $age['image'] ?? null,
                    'crawler_page_url'     => $task->crawlerTaskItem->crawl_location ?? null,
                    'ai_result'            => $score >= 0.7 ? 'abnormal' : 'normal',
                    'status'               => 'valid',
                    'reason'               => $score >= 0.7 ? 'minor_probability' : null,
                    'ai_score'             => $score,
                    'keywords'             => $result->keywords,
                ]);
            }

            foreach ($nsfws as $nsfw) {

                $porn = (float) (($nsfw['result']['porn'] ?? 0));

                AiPredictResultItem::create([
                    'id'                   => (string) Str::uuid(),
                    'ai_predict_result_id' => $result->id,
                    'media_url'            => $nsfw['image'] ?? null,
                    'crawler_page_url'     => $task->crawlerTaskItem->crawl_location ?? null,
                    'ai_result'            => $porn >= 0.6 ? 'abnormal' : 'normal',
                    'status'               => 'valid',
                    'reason'               => $porn >= 0.6 ? 'nsfw_porn' : null,
                    'ai_score'             => $porn,
                    'keywords'             => $result->keywords,
                ]);
            }

            return $result;
        });
    }

    public function evidenceReview(string $resultId, array $items): void
    {
        DB::transaction(function () use ($resultId, $items) {

            $result = AiPredictResult::with('caseManagement')
                ->findOrFail($resultId);

            $case = $result->caseManagement;

            foreach ($items as $data) {

                $item = AiPredictResultItem::where(
                    'ai_predict_result_id',
                    $resultId
                )
                    ->where('id', $data['id'])
                    ->firstOrFail();

                $item->update([
                    'status'       => $data['decision'],
                    'reason'       => $data['decision'] === 'invalid'
                        ? $data['reason']
                        : null,
                    'other_reason' => (
                        $data['decision'] === 'invalid'
                        && $data['reason'] === 'Other'
                    )
                        ? $data['other_reason']
                        : null,
                ]);

                if ($data['decision'] === 'invalid') {

                    CaseManagementItem::create([
                        'case_management_id' => $case->id,
                        'media_url'          => $item->media_url,
                        'crawler_page_url'   => $item->crawler_page_url,
                        'ai_result'          => $item->ai_result,
                        'status'             => 'invalid',
                        'reason'             => $data['reason'],
                        'other_reason'       => $data['reason'] === 'Other'
                            ? $data['other_reason']
                            : null,
                        'ai_score'           => $item->ai_score,
                        'keywords'           => $item->keywords,
                        'issue_date'         => now(),
                    ]);
                }
            }

            $case->update([
                'status' => 'created',
            ]);
        });
    }
}
