<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditRatio;
use App\Models\AiPredictResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditRatioController extends Controller
{
    /**
     * Get the current audit ratio setting
     */
    public function index(): JsonResponse
    {
        $auditRatio = AuditRatio::query()->latest()->first();

        if (!$auditRatio) {
            // Create default if none exists
            $auditRatio = AuditRatio::create([
                'id' => (string) Str::uuid(),
                'ratio' => 0.0001, // Default: 1/10000
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $auditRatio->id,
                'ratio' => $auditRatio->ratio,
                'threshold' => $this->calculateThreshold($auditRatio->ratio),
            ],
        ]);
    }

    /**
     * Update the audit ratio
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'ratio' => [
                'required',
                'numeric',
                'min:0.0001',
                'max:1',
                'regex:/^\d+(\.\d+)?$/', // Decimal format only
            ],
        ], [
            'ratio.required' => '審核比例為必填欄位。',
            'ratio.numeric' => '審核比例必須是數字。',
            'ratio.min' => '審核比例最小值為 0.0001。',
            'ratio.max' => '審核比例最大值為 1。',
            'ratio.regex' => '審核比例格式必須為小數點格式。',
        ]);

        $auditRatio = AuditRatio::findOrFail($id);
        $auditRatio->update([
            'ratio' => $validated['ratio'],
        ]);

        return response()->json([
            'message' => '審核比例已更新。',
            'data' => [
                'id' => $auditRatio->id,
                'ratio' => $auditRatio->ratio,
                'threshold' => $this->calculateThreshold($auditRatio->ratio),
            ],
        ]);
    }

    /**
     * Get audit status - check if audit is needed
     */
    public function getAuditStatus(): JsonResponse
    {
        $auditRatio = AuditRatio::query()->latest()->first();

        if (!$auditRatio) {
            $auditRatio = AuditRatio::create([
                'id' => (string) Str::uuid(),
                'ratio' => 0.0001,
            ]);
        }

        $threshold = $this->calculateThreshold($auditRatio->ratio);

        // Count unaudited results since last audit
        $lastAuditDate = AiPredictResult::query()
            ->whereNotNull('audit_date')
            ->latest('audit_date')
            ->value('audit_date');

        $query = AiPredictResult::query()->where('audit_status', '=', 'pending');

        if ($lastAuditDate) {
            $query->where('created_at', '>', $lastAuditDate);
        }

        $unauditedCount = $query->count();

        $needsAudit = $unauditedCount >= $threshold;

        return response()->json([
            'data' => [
                'needs_audit' => $needsAudit,
                'unaudited_count' => $unauditedCount,
                'threshold' => $threshold,
                'ratio' => $auditRatio->ratio,
                'audit_ratio_id' => $auditRatio->id,
            ],
        ]);
    }

    /**
     * Calculate threshold from ratio
     * Example: 0.0001 = 1/10000 = 10000 items needed
     */
    private function calculateThreshold(float $ratio): int
    {
        if ($ratio <= 0) {
            return 10000; // Default
        }

        return (int) round(1 / $ratio);
    }
}
