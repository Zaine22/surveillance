<?php
namespace App\Services;

use App\Models\AiModelTask;
use App\Models\AiPredictResult;
use App\Models\AiPredictResultAudit;
use App\Models\AiPredictResultItem;
use App\Models\CaseManagementItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiPredictResultService extends BaseFilterService
{
    public function __construct(protected CaseManagementService $service)
    {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = AiPredictResult::with('aiModelTask');

        if (! empty($filters['search'])) {
            $search = strtolower($filters['search']);

            $query->where(function ($q) use ($search) {

                $q->orWhereRaw(
                    'LOWER(analysis_result) LIKE ?',
                    ["%{$search}%"]
                )
                    ->orWhereHas('aiModelTask', function ($task) use ($search) {
                        $task->whereRaw(
                            'LOWER(file_name) LIKE ?',
                            ["%{$search}%"]
                        );
                    });
            });
        }

        if (! empty($filters['review_status'])) {
            $query->where('review_status', $filters['review_status']);
        }

        if (! empty($filters['ai_analysis_result'])) {
            $query->where('ai_analysis_result', $filters['ai_analysis_result']);
        }

        if (empty($filters['range'])) {
            $filters['range'] = 'one_week';
        } else if ($filters['range'] === 'custom_range') {
            $filters['range'] = null;
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

    public function saveFromAiCallback(string $id, array $payload): AiPredictResult
    {
        $task = AiModelTask::with([
            'crawlerTaskItem.crawlerTask.lexicon.keywords',
        ])->findOrFail($id);

        return DB::transaction(function () use ($task, $payload) {

            $existing = AiPredictResult::where('ai_model_task_id', $task->id)->first();
            if ($existing) {
                Log::info('AI RESULT ALREADY EXISTS', ['task_id' => $task->id]);

                return $existing;
            }

            $status = strtolower((string) ($payload['status'] ?? ''));

            if (in_array($status, ['pending', 'running'])) {
                throw new \RuntimeException("AI not finished: {$status}");
            }

            if ($status === 'failed') {
                return $this->createFailedResult($task, $payload);
            }

            if ($status !== 'finished') {
                throw new \RuntimeException("Unsupported status: {$status}");
            }

            $parsed = $payload;

            $victims = $parsed['victim'] ?? [];
            $ages    = $parsed['age'] ?? [];
            $nsfws   = $parsed['nsfw'] ?? [];

            $hasVictim    = $this->hasVictim($victims);
            $maxAgeScore  = $this->getMaxAgeScore($ages);
            $maxPornScore = $this->getMaxPornScore($nsfws);

            $aiAnalysisResult = 'normal';
            $aiScore          = max($maxAgeScore, $maxPornScore);

            if ($hasVictim) {
                $aiAnalysisResult = 'abnormal';
                $aiScore          = 1.00;
            } elseif ($maxAgeScore >= 0.70 || $maxPornScore >= 0.60) {
                $aiAnalysisResult = 'abnormal';
            }

            $lexicon  = $task->crawlerTaskItem?->crawlerTask?->lexicon;
            $keywords = $this->collectLexiconKeywords($lexicon);

            $result = AiPredictResult::create([
                'id'                 => (string) Str::uuid(),
                'ai_model_task_id'   => $task->id,
                'lexicon_id'         => $lexicon?->id,
                'keywords'           => $keywords,
                'ai_score'           => round($aiScore, 2),
                'analysis_result'    => json_encode($parsed, JSON_UNESCAPED_UNICODE),
                'ai_analysis_result' => $aiAnalysisResult,
                'ai_analysis_detail' => $parsed,
                'review_status'      => 'pending',
                'audit_status'       => 'pending',
            ]);

            $this->createVictimItems($result, $task, $victims);
            $this->createAgeItems($result, $task, $ages);
            $this->createNsfwItems($result, $task, $nsfws);

            return $result;
        });
    }
    protected function collectLexiconKeywords($lexicon): array
    {
        $keywords = [];

        if ($lexicon && $lexicon->keywords) {
            foreach ($lexicon->keywords as $row) {

                $value = $row->keywords;

                if (is_array($value)) {
                    $keywords = array_merge($keywords, $value);
                } elseif (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (is_array($decoded)) {
                        $keywords = array_merge($keywords, $decoded);
                    }
                }
            }
        }

        return array_values(array_unique($keywords));
    }

    protected function createFailedResult(AiModelTask $task, array $payload): AiPredictResult
    {
        $lexicon  = $task->crawlerTaskItem?->crawlerTask?->lexicon;
        $keywords = [];

        if ($lexicon && $lexicon->keywords) {
            foreach ($lexicon->keywords as $row) {

                $value = $row->keywords;

                if (is_array($value)) {
                    $keywords = array_merge($keywords, $value);
                } elseif (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (is_array($decoded)) {
                        $keywords = array_merge($keywords, $decoded);
                    }
                }
            }
        }

        $keywords = array_values(array_unique($keywords));

        return AiPredictResult::create([
            'id'                 => (string) Str::uuid(),
            'ai_model_task_id'   => $task->id,
            'lexicon_id'         => $lexicon?->id,
            'keywords'           => $this->extractKeywords($keywords),
            'ai_score'           => 0.00,
            'analysis_result'    => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'ai_analysis_result' => 'normal',
            'ai_analysis_detail' => $payload,
            'review_status'      => 'pending',
            'audit_status'       => 'pending',
        ]);
    }

    protected function hasVictim(array $victims): bool
    {
        foreach ($victims as $victim) {
            if (! empty($victim['victims']) && is_array($victim['victims'])) {
                return true;
            }
        }

        return false;
    }

    protected function getMaxAgeScore(array $ages): float
    {
        $maxAgeScore = 0.0;

        foreach ($ages as $age) {
            $score       = (float) ($age['underage_probability'] ?? 0);
            $maxAgeScore = max($maxAgeScore, $score);
        }

        return $maxAgeScore;
    }

    protected function getMaxPornScore(array $nsfws): float
    {
        $maxPornScore = 0.0;

        foreach ($nsfws as $nsfw) {
            $porn         = (float) ($nsfw['result']['porn'] ?? 0);
            $maxPornScore = max($maxPornScore, $porn);
        }

        return $maxPornScore;
    }

    protected function createVictimItems(
        AiPredictResult $result,
        AiModelTask $task,
        array $victims
    ): void {
        foreach ($victims as $victim) {
            $image = $victim['image'] ?? null;
            $faces = $victim['victims'] ?? [];

            if (! is_array($faces)) {
                continue;
            }

            foreach ($faces as $face) {
                AiPredictResultItem::create([
                    'id'                   => (string) Str::uuid(),
                    'ai_predict_result_id' => $result->id,
                    'media_url'            => $image,
                    'crawler_page_url'     => $task->crawlerTaskItem?->crawl_location,
                    'ai_result'            => 'abnormal',
                    'status'               => 'valid',
                    'reason'               => 'victim_detected',
                    'other_reason'         => null,
                    'ai_score'             => round((float) ($face['similarity'] ?? 1), 2),
                    'keywords'             => $result->keywords,
                ]);
            }
        }
    }

    protected function createAgeItems(
        AiPredictResult $result,
        AiModelTask $task,
        array $ages
    ): void {
        foreach ($ages as $age) {
            $score = (float) ($age['underage_probability'] ?? 0);

            AiPredictResultItem::create([
                'id'                   => (string) Str::uuid(),
                'ai_predict_result_id' => $result->id,
                'media_url'            => $age['path'] ?? null,
                'crawler_page_url'     => $task->crawlerTaskItem?->crawl_location,
                'ai_result'            => $score >= 0.70 ? 'abnormal' : 'normal',
                'status'               => 'valid',
                'reason'               => $score >= 0.70 ? 'minor_probability' : null,
                'other_reason'         => null,
                'ai_score'             => round($score, 2),
                'keywords'             => $result->keywords, ($result->keywords),
            ]);
        }
    }

    protected function createNsfwItems(
        AiPredictResult $result,
        AiModelTask $task,
        array $nsfws
    ): void {
        foreach ($nsfws as $nsfw) {
            $porn = (float) ($nsfw['result']['porn'] ?? 0);

            AiPredictResultItem::create([
                'id'                   => (string) Str::uuid(),
                'ai_predict_result_id' => $result->id,
                'media_url'            => $nsfw['image'] ?? null,
                'crawler_page_url'     => $task->crawlerTaskItem?->crawl_location,
                'ai_result'            => $porn >= 0.60 ? 'abnormal' : 'normal',
                'status'               => 'valid',
                'reason'               => $porn >= 0.60 ? 'nsfw_porn' : null,
                'other_reason'         => null,
                'ai_score'             => round($porn, 2),
                'keywords'             => $result->keywords, ($result->keywords),
            ]);
        }
    }

    protected function extractKeywords(mixed $keywords): ?string
    {
        if (empty($keywords)) {
            return null;
        }

        if (is_string($keywords)) {
            return $keywords;
        }

        if (is_array($keywords)) {
            return json_encode($keywords, JSON_UNESCAPED_UNICODE);
        }

        return null;
    }

    protected function normalizeKeywordsForJson(mixed $keywords): ?array
    {
        if (empty($keywords)) {
            return null;
        }

        if (is_array($keywords)) {
            return $keywords;
        }

        if (is_string($keywords)) {
            $decoded = json_decode($keywords, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            return [$keywords];
        }

        return null;
    }

    public function evidenceReview(string $resultId, array $items): AiPredictResult
    {
        return DB::transaction(function () use ($resultId, $items) {

            $result = AiPredictResult::with('caseManagement')
                ->findOrFail($resultId);

            $case = $result->caseManagement;

            $validCount   = 0;
            $invalidCount = 0;

            foreach ($items as $data) {

                $decision = strtolower($data['decision']);

                $item = AiPredictResultItem::where(
                    'ai_predict_result_id',
                    $resultId
                )
                    ->where('id', $data['id'])
                    ->firstOrFail();

                $item->update([
                    'status'       => $decision,
                    'reason'       => $decision === 'invalid'
                        ? ($data['reason'] ?? null)
                        : null,
                    'other_reason' => (
                        $decision === 'invalid'
                        && ($data['reason'] ?? null) === 'Other'
                    )
                        ? ($data['other_reason'] ?? null)
                        : null,
                ]);

                if ($decision === 'valid') {
                    $validCount++;
                }

                if ($decision === 'invalid') {

                    $invalidCount++;

                    CaseManagementItem::create([
                        'case_management_id' => $case->id,
                        'media_url'          => $item->media_url,
                        'crawler_page_url'   => $item->crawler_page_url,
                        'ai_result'          => $item->ai_result,
                        'status'             => 'invalid',
                        'reason'             => $data['reason'] ?? null,
                        'other_reason'       => ($data['reason'] ?? null) === 'Other'
                            ? ($data['other_reason'] ?? null)
                            : null,
                        'ai_score'           => $item->ai_score,
                        'keywords'           => json_encode($item->keywords),
                        'issue_date'         => now(),
                    ]);
                }
            }

            $finalDecision = $invalidCount > 0
                ? 'rejected'
                : 'approved';

            $result->update([
                'review_status' => $finalDecision,
            ]);

            AiPredictResultAudit::create([
                'ai_predict_result_id' => $resultId,
                'auditor_id'           => auth()->id(),
                'decision'             => $finalDecision,
                'valid_count'          => $validCount,
                'invalid_count'        => $invalidCount,
                'summary'              => "Valid: {$validCount}, Invalid: {$invalidCount}",
            ]);

            if ($case) {

                if ($invalidCount > 0) {
                    // illegal content → notify authority
                    $case->update([
                        'status' => 'notified',
                    ]);
                } else {
                    // no issue → close case
                    $case->update([
                        'status' => 'case_not_established',
                    ]);
                }
            }

            return $result->fresh([
                'items',
                'caseManagement.items',
                'audits.auditor',
            ]);
        });
    }
}
