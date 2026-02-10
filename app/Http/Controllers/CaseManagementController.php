<?php

namespace App\Http\Controllers;

use App\Http\Requests\CaseManagement\ProceedCaseScreenshotRequest;
use App\Http\Requests\CaseManagement\StoreExternalCaseRequest;
use App\Http\Requests\CaseManagement\UpdateCaseScreenshotRequest;
use App\Models\CaseFeedback;
use App\Services\CaseManagementService;

class CaseManagementController extends Controller
{
    public function __construct(private readonly CaseManagementService $caseManagementService) {}

    public function netChineseCaseFeedback(StoreCaseFeedbackRequest $request)
    {
        $validated = $request->validated();

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

    public function externalCaseCreate(StoreExternalCaseRequest $request)
    {
        $validated = $request->validated();

        $result = $this->caseManagementService->createExternalCase($validated);

        if ($result === null) {
            return response()->json([
                'status' => 'error',
                'message' => '外部案件创建失败',
            ], 500);
        }

        return response()->json(['status' => 'success', 'message' => '已接收'], 201);
    }

    public function netChineseCaseScreenshot(ProceedCaseScreenshotRequest $request)
    {
        $validated = $request->validated();

        $result = $this->caseManagementService->caseScreenShot($validated);

        return response()->json($result);
    }

    public function captureCaseScreenshot(string $caseItemId, UpdateCaseScreenshotRequest $request)
    {
        $validated = $request->validated();

        $result = $this->caseManagementService->captureCaseScreenshot($caseItemId, $validated);

        return response()->json($result);
    }
}
