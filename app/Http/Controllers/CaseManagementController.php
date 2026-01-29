<?php

namespace App\Http\Controllers;

use App\Models\CaseFeedback;
use App\Services\CaseManagementService;

class CaseManagementController extends Controller
{
    public function __construct(private readonly CaseManagementService $caseManagementService) {}

    public function netChineseCaseFeedback()
    {
        $validated = request()->validate([
            'case_id' => 'required|string',
            'url' => 'required|url',
            'is_ilLegal' => 'required|boolean',
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

    public function netChineseCaseCreate()
    {
        return response()->json(['message' => 'API key valid. Access granted to net Chinese case creation.'], 200);
    }

    public function netChineseCaseScreenshot()
    {
        return response()->json(['message' => 'API key valid. Access granted to net Chinese case screenshot.'], 200);
    }
}
