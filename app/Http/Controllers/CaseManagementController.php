<?php

namespace App\Http\Controllers;

use App\Models\CaseFeedback;
use App\Services\CaseManagementService;
use Illuminate\Http\Request;

class CaseManagementController extends Controller
{
    public function __construct(private readonly CaseManagementService $caseManagementService) {}

    public function netChineseCaseFeedback()
    {
        $validated = request()->validate([
            'case_id' => 'required|string',
            'url' => 'required|url',
            'is_illegal' => 'required|boolean',
            'legal_basis' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);

        $result = $this->caseManagementService->createCaseFeedback($validated);
        if ($result instanceof CaseFeedback === false) {
            return response()->json([
                'status' => 'error',
                'message' => '反馈保存失败',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => '已接收',
        ], 200);
    }

    public function externalCaseCreate()
    {
        request()->validate([
            'url' => 'required|url',
            'leakReason' => 'required|string',
        ]);

        $result = $this->caseManagementService->createExternalCase(request()->only(['url', 'leakReason']));

        if ($result === null) {
            return response()->json([
                'status' => 'error',
                'message' => '外部案件创建失败',
            ], 500);
        }

        return response()->json(['status' => 'success', 'message' => '已接收'], 201);
    }

    public function netChineseCaseScreenshot()
    {
        $validated = request()->validate([
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'url' => 'required|url',
        ]);

        $result = $this->caseManagementService->caseScreenShot($validated);

        return response()->json($result);
    }

    public function captureCaseScreenshot(string $caseItemId, Request $request)
    {
        $validated = $request->validate([
            'media_url' => 'required|string',
        ]);

        $result = $this->caseManagementService->captureCaseScreenshot($caseItemId, $validated);

        return response()->json(['status' => 'success', 'data' => $result]);
    }
}
