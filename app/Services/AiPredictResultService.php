<?php

namespace App\Services;

use App\Models\AiModelTask;
use App\Models\AiPredictResult;
use App\Models\AiPredictResultItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiPredictResultService
{
    public function __construct() {}

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
