<?php

namespace App\Services;

use App\Models\AiModelTask;
use App\Models\AiPredictResult;
use App\Models\AiPredictResultItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AiPredictResultService extends BaseFilterService
{
    public function __construct() {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = AiPredictResult::with('aiModelTask');

        if (!empty($filters['search'])) {
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

        if (!empty($filters['review_status'])) {
            $query->where('review_status', $filters['review_status']);
        }

        if (!empty($filters['audit_status'])) {
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
    public function saveFromAiCallback(AiModelTask $task, array $payload): AiPredictResult
    {
        return DB::transaction(function () use ($task, $payload) {

            $result = AiPredictResult::create([
                'id' => (string) Str::uuid(),
                'ai_model_task_id' => $task->id,
                'ai_score' => $payload['ai_score'] ?? null,
                'ai_analysis_result' => $payload['ai_analysis_result'] ?? null,
                'review_status' => 'pending',
                'audit_status' => 'pending',
                'keywords' => $payload['keywords'] ?? null,
            ]);

            foreach ($payload['items'] ?? [] as $item) {
                AiPredictResultItem::create([
                    'id' => (string) Str::uuid(),
                    'ai_predict_result_id' => $result->id,
                    'media_url' => $item['media_url'] ?? null,
                    'crawler_page_url' => $item['crawler_page_url'] ?? null,
                    'ai_result' => $item['ai_result'] ?? null,
                    'status' => 'valid',
                ]);
            }

            return $result;
        });
    }
}
