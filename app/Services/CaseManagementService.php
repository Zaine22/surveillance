<?php

namespace App\Services;

use App\Models\AiPredictResult;
use App\Models\CaseFeedback;
use App\Models\CaseManagement;
use App\Models\CaseManagementItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CaseManagementService
{
    public function __construct() {}

    public function createFromPredictResult(AiPredictResult $result): CaseManagement
    {
        return DB::transaction(function () use ($result) {

            $case = CaseManagement::create([
                'id' => (string) Str::uuid(),
                'ai_predict_result_id' => $result->id,
                'keywords' => $result->keywords,
                'status' => 'created',
            ]);

            foreach ($result->items as $item) {
                CaseManagementItem::create([
                    'id' => (string) Str::uuid(),
                    'case_management_id' => $case->id,
                    'media_url' => $item->media_url,
                    'crawler_page_url' => $item->crawler_page_url,
                    'ai_result' => $item->ai_result,
                    'status' => $item->status,
                ]);
            }

            return $case;
        });
    }

    public function createCaseFeedback(array $data): CaseFeedback
    {
        $case = CaseFeedback::updateOrCreate(
            ['case_id' => $data['case_id']],
            [
                'url' => $data['url'],
                'is_illegal' => $data['is_illegal'],
                'legal_basis' => $data['legal_basis'] ?? null,
                'reason' => $data['reason'] ?? null,
            ]
        );

        return $case;
    }

    public function createExternalCase(array $data): CaseManagementItem
    {
        $external_case_id = 'EXT-'.strtoupper(uniqid());
        $case = CaseManagement::create([
            'external_case_no' => $external_case_id,
        ]);

        $caseItem = CaseManagementItem::create([
            'case_management_id' => $case->id,
            'crawler_page_url' => $data['url'],
            'status' => 'valid',
            'reason' => $data['leakReason'],
        ]);

        return $caseItem;
    }

    public function caseScreenShot(array $validated)
    {

        $case = CaseManagementItem::where('crawler_page_url', $validated['url'])->firstOrFail();

        $case->update([
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
        ]);

        $response = Http::post(
            env('SCREENSHOT_URL'),
            [
                'case_management_id' => $case->case_management_id,
                'case_management_item_id' => $case->id, // or item_id if you have it
                'url' => $case->crawler_page_url,
            ]
        );

        if ($response->failed()) {
            return response()->json([
                'message' => 'Case updated but crawler API failed',
                'error' => $response->body(),
            ], 500);
        }

        return response()->json([
            'message' => 'Case updated successfully',
            'data' => $case,
        ]);
    }

    public function captureCaseScreenshot(string $id, array $data)
    {
        try {
            $case = CaseManagementItem::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Case not found');
        }

        $case->update([
            'media_url' => $data['media_url'],
            'status' => $data['status'] ?? $case->status,
        ]);

        return $case;
    }
}
