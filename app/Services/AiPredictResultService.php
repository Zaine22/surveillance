<?php
namespace App\Services;

use App\Models\AiModelTask;
use App\Models\AiPredictResult;
use App\Models\AiPredictResultAudit;
use App\Models\AiPredictResultItem;
use App\Models\CaseManagementItem;
use App\Models\LexiconKeyword;
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
            $search = $filters['search'];

            $query->whereHas('aiModelTask', function ($q) use ($search) {
                $q->where('file_name', 'LIKE', "%{$search}%");
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
                // throw new \RuntimeException("AI not finished: {$status}");
                throw new \RuntimeException("AI 未完成：{$status}");
            }

            if ($status === 'failed') {
                $result = $this->createFailedResult($task, $payload);

                // Update task status to completed after processing failed result
                $task->update(['status' => 'completed']);

                Log::info('AI task status updated to completed (failed result)', [
                    'task_id' => $task->id,
                    'result_id' => $result->id,
                ]);

                return $result;
            }

            if ($status !== 'finished') {
                // throw new \RuntimeException("Unsupported status: {$status}");
                throw new \RuntimeException("不支援的狀態：{$status}");
            }

            $parsed = $this->parseAiResult($payload);

            $victims = $parsed['victim'] ?? [];
            $ages    = $parsed['age'] ?? [];
            $nsfws   = $parsed['nsfw'] ?? [];
            Log::info('AI PARSED CHECK', [
                'task_id'       => $task->id,
                'parsed'        => $parsed,
                'victim_count'  => count($victims),
                'age_count'     => count($ages),
                'nsfw_count'    => count($nsfws),
                'max_age_score' => $this->getMaxAgeScore($ages),
            ]);
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
            $keywords = $task->crawlerTaskItem?->keywords ?? [];

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

            // Update task status to completed after successfully processing result
            $task->update(['status' => 'completed']);

            Log::info('AI task status updated to completed', [
                'task_id' => $task->id,
                'result_id' => $result->id,
                'ai_analysis_result' => $aiAnalysisResult,
            ]);

            return $result;
        });
    }
    protected function parseAiResult(array $payload): array
    {
        if (isset($payload['parsed']) && is_array($payload['parsed'])) {
            return $payload['parsed'];
        }

        $result = $payload['result'] ?? null;

        if (is_array($result)) {
            return $result;
        }

        if (is_string($result)) {
            $decoded = json_decode($result, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            $fixed = str_replace(
                ["'", 'None', 'True', 'False'],
                ['"', 'null', 'true', 'false'],
                $result
            );

            $decoded = json_decode($fixed, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            'victim' => [],
            'age'    => [],
            'nsfw'   => [],
        ];
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
        $keywords = $task->crawlerTaskItem?->keywords ?? [];

        return AiPredictResult::create([
            'id'                 => (string) Str::uuid(),
            'ai_model_task_id'   => $task->id,
            'lexicon_id'         => $lexicon?->id,
            'keywords'           => $keywords,
            'ai_score'           => 0.00,
            'analysis_result'    => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'ai_analysis_result' => 'abnormal',
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
        $createdItems = [];

        foreach ($victims as $victim) {
            $image = $victim['image'] ?? null;
            $faces = $victim['victims'] ?? [];

            if (! is_array($faces)) {
                continue;
            }

            foreach ($faces as $face) {
                $mediaUrl = $this->buildMediaUrl($image);
                $aiScore = round((float) ($face['similarity'] ?? 1), 2);

                // Create unique key to prevent duplicates
                $uniqueKey = md5($result->id . '|' . $mediaUrl . '|' . $aiScore . '|victim_detected');

                if (isset($createdItems[$uniqueKey])) {
                    Log::info('Skipping duplicate victim item', [
                        'result_id' => $result->id,
                        'media_url' => $mediaUrl,
                        'ai_score' => $aiScore,
                    ]);
                    continue;
                }

                AiPredictResultItem::create([
                    'id'                   => (string) Str::uuid(),
                    'ai_predict_result_id' => $result->id,
                    'media_url'            => $mediaUrl,
                    'crawler_page_url'     => $task->crawlerTaskItem?->crawl_location,
                    'ai_result'            => 'abnormal',
                    'status'               => 'valid',
                    'reason'               => 'victim_detected',
                    'other_reason'         => null,
                    'ai_score'             => $aiScore,
                    'keywords'             => $result->keywords,
                ]);

                $createdItems[$uniqueKey] = true;
            }
        }
    }

    // protected function createAgeItems(
    //     AiPredictResult $result,
    //     AiModelTask $task,
    //     array $ages
    // ): void {
    //     foreach ($ages as $age) {
    //         $score = (float) ($age['underage_probability'] ?? 0);

    //         AiPredictResultItem::create([
    //             'id'                   => (string) Str::uuid(),
    //             'ai_predict_result_id' => $result->id,
    //             'media_url'            => $age['path'] ?? null,
    //             'crawler_page_url'     => $task->crawlerTaskItem?->crawl_location,
    //             'ai_result'            => $score >= 0.70 ? 'abnormal' : 'normal',
    //             'status'               => 'valid',
    //             'reason'               => $score >= 0.70 ? 'minor_probability' : null,
    //             'other_reason'         => null,
    //             'ai_score'             => round($score, 2),
    //             'keywords'             => $result->keywords, ($result->keywords),
    //         ]);
    //     }
    // }
    protected function createAgeItems(
        AiPredictResult $result,
        AiModelTask $task,
        array $ages
    ): void {
        $createdItems = [];

        foreach ($ages as $age) {
            $score = (float) ($age['underage_probability'] ?? 0);
            $mediaUrl = $this->buildMediaUrl($age['path'] ?? null);
            $aiResult = $score >= 0.70 ? 'abnormal' : 'normal';
            $reason = $score >= 0.70 ? 'minor_probability' : null;
            $aiScore = round($score, 2);

            // Create unique key to prevent duplicates
            $uniqueKey = md5($result->id . '|' . $mediaUrl . '|' . $aiScore . '|' . $reason);

            if (isset($createdItems[$uniqueKey])) {
                Log::info('Skipping duplicate age item', [
                    'result_id' => $result->id,
                    'media_url' => $mediaUrl,
                    'ai_score' => $aiScore,
                ]);
                continue;
            }

            AiPredictResultItem::create([
                'id'                   => (string) Str::uuid(),
                'ai_predict_result_id' => $result->id,
                'media_url'            => $mediaUrl,
                'crawler_page_url'     => $task->crawlerTaskItem?->crawl_location,
                'ai_result'            => $aiResult,
                'status'               => 'valid',
                'reason'               => $reason,
                'other_reason'         => null,
                'ai_score'             => $aiScore,
                'keywords'             => $result->keywords,
            ]);

            $createdItems[$uniqueKey] = true;
        }
    }

    // protected function createNsfwItems(
    //     AiPredictResult $result,
    //     AiModelTask $task,
    //     array $nsfws
    // ): void {
    //     foreach ($nsfws as $nsfw) {
    //         $porn = (float) ($nsfw['result']['porn'] ?? 0);

    //         AiPredictResultItem::create([
    //             'id'                   => (string) Str::uuid(),
    //             'ai_predict_result_id' => $result->id,
    //             'media_url'            => $nsfw['image'] ?? null,
    //             'crawler_page_url'     => $task->crawlerTaskItem?->crawl_location,
    //             'ai_result'            => $porn >= 0.60 ? 'abnormal' : 'normal',
    //             'status'               => 'valid',
    //             'reason'               => $porn >= 0.60 ? 'nsfw_porn' : null,
    //             'other_reason'         => null,
    //             'ai_score'             => round($porn, 2),
    //             'keywords'             => $result->keywords, ($result->keywords),
    //         ]);
    //     }
    // }

    protected function createNsfwItems(
        AiPredictResult $result,
        AiModelTask $task,
        array $nsfws
    ): void {
        $createdItems = [];

        foreach ($nsfws as $nsfw) {
            $porn = (float) ($nsfw['result']['porn'] ?? 0);
            $mediaUrl = $this->buildMediaUrl($nsfw['image'] ?? null);
            $aiResult = $porn >= 0.60 ? 'abnormal' : 'normal';
            $reason = $porn >= 0.60 ? 'nsfw_porn' : null;
            $aiScore = round($porn, 2);

            // Create unique key to prevent duplicates
            $uniqueKey = md5($result->id . '|' . $mediaUrl . '|' . $aiScore . '|' . $reason);

            if (isset($createdItems[$uniqueKey])) {
                Log::info('Skipping duplicate NSFW item', [
                    'result_id' => $result->id,
                    'media_url' => $mediaUrl,
                    'ai_score' => $aiScore,
                ]);
                continue;
            }

            AiPredictResultItem::create([
                'id'                   => (string) Str::uuid(),
                'ai_predict_result_id' => $result->id,
                'media_url'            => $mediaUrl,
                'crawler_page_url'     => $task->crawlerTaskItem?->crawl_location,
                'ai_result'            => $aiResult,
                'status'               => 'valid',
                'reason'               => $reason,
                'other_reason'         => null,
                'ai_score'             => $aiScore,
                'keywords'             => $result->keywords,
            ]);

            $createdItems[$uniqueKey] = true;
        }
    }

    protected function buildMediaUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Get the base URL from environment
        $baseUrl = rtrim(config('app.ai_media_base_url', 'http://220.130.187.241:9680/mnt'), '/');

        // Handle absolute paths like /home/victor/MOHW/task/...
        // Extract the path starting from 'task/' onwards (case-insensitive)
        if (preg_match('#/task/(.+)$#i', $path, $matches)) {
            $path = 'task/' . $matches[1];
        } else {
            // Remove leading slash if present to avoid double slashes
            $path = ltrim($path, '/');
        }

        return "{$baseUrl}/{$path}";
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

            // If no case exists, create one
            if (!$case) {
                $case = $this->service->createFromPredictResult($result);
                $result->refresh();
            }

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

                    CaseManagementItem::create([
                        'case_management_id' => $case->id,
                        'media_url'          => $item->media_url,
                        'crawler_page_url'   => $item->crawler_page_url,
                        'ai_result'          => $item->ai_result,
                        'status'             => 'valid',
                        'reason'             => null,
                        'other_reason'       => null,
                        'ai_score'           => $item->ai_score,
                        'keywords'           => json_encode($item->keywords),
                        'issue_date'         => now(),
                    ]);
                }

                if ($decision === 'invalid') {

                    $invalidCount++;
                }
            }

            $finalDecision = $validCount > 0
                ? 'approved'
                : 'rejected';

            $result->update([
                'review_status' => $finalDecision,
                'audit_status'  => $finalDecision,
                'audit_date'    => now(),
            ]);

            AiPredictResultAudit::create([
                'ai_predict_result_id' => $resultId,
                'auditor_id'           => auth()->id(),
                'decision'             => $finalDecision,
                'valid_count'          => $validCount,
                'invalid_count'        => $invalidCount,
                'summary'              => "Valid: {$validCount}, Invalid: {$invalidCount}",
            ]);

            $case->update([
                'status' => $validCount > 0
                    ? 'notified'
                    : 'case_not_established',
            ]);

            // Increment lexicon keyword case_count if case is established (valid items exist)
            if ($validCount > 0 && $result->lexicon_id) {
                $this->incrementLexiconCaseCount($result);
            }

            return $result->fresh([
                'items',
                'caseManagement.items',
                'audits.auditor',
            ]);
        });
    }

    /**
     * Increment case_count for lexicon keywords that match the AI result keywords
     */
    protected function incrementLexiconCaseCount(AiPredictResult $result): void
    {
        try {
            // Get the keywords from the result
            $resultKeywords = $this->normalizeKeywordsForJson($result->keywords);

            if (empty($resultKeywords)) {
                Log::info('No keywords found in AI result to increment case count', [
                    'result_id' => $result->id,
                ]);
                return;
            }

            // Find all lexicon keywords that match
            $lexiconKeywords = LexiconKeyword::where('lexicon_id', $result->lexicon_id)
                ->where('status', 'enabled')
                ->get();

            $incrementedCount = 0;
            foreach ($lexiconKeywords as $lexiconKeyword) {
                $lexiconKeywordArray = is_array($lexiconKeyword->keywords)
                    ? $lexiconKeyword->keywords
                    : json_decode($lexiconKeyword->keywords, true);

                if (!is_array($lexiconKeywordArray)) {
                    continue;
                }

                // Check if any result keyword matches this lexicon keyword
                $hasMatch = !empty(array_intersect($resultKeywords, $lexiconKeywordArray));

                if ($hasMatch) {
                    $lexiconKeyword->increment('case_count');
                    $incrementedCount++;

                    Log::info('Incremented case_count for lexicon keyword', [
                        'lexicon_keyword_id' => $lexiconKeyword->id,
                        'new_case_count' => $lexiconKeyword->case_count + 1,
                        'matched_keywords' => array_intersect($resultKeywords, $lexiconKeywordArray),
                    ]);
                }
            }

            Log::info('Lexicon case count increment completed', [
                'result_id' => $result->id,
                'lexicon_id' => $result->lexicon_id,
                'incremented_keywords' => $incrementedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to increment lexicon case count', [
                'result_id' => $result->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
