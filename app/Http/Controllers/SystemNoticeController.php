<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\SystemNotice\StoreNoticeRequest;
use App\Http\Requests\SystemNotice\GetAllNoticeRequest;
use App\Http\Requests\SystemNotice\UpdateNoticeRequest;
use App\Services\SystemNoticeService;

class SystemNoticeController extends Controller
{
    public function __construct(
        private readonly SystemNoticeService $systemNoticeService
    ) {}

    public function index(GetAllNoticeRequest $request)
    {
        $validated = $request->validated();
        $notices = $this->systemNoticeService->getAllNotices($validated);
        return response()->json([
            'message' => '系统公告列表',
            'data' => $notices,
        ]);
    }

    public function show($id)
    {
        try {
            $result = $this->systemNoticeService->getNoticeById($id);

            return response()->json([
                'message' => '显示系统公告',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve system notice',
            ], 404);
        }
    }

    public function store(StoreNoticeRequest $request)
    {
        $validated = $request->validated();

        try {

            $result = $this->systemNoticeService->createNotice($validated);

            return response()->json([
                'message' => '系统公告创建成功',
                'data' => $result,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Failed to create system notice',
            ], 500);
        }
    }

    public function getActiveNotices() {
        $result = $this->systemNoticeService->getActiveNotices();

        return response()->json([
            'message' => '获取当前有效的系统公告',
            'data' => $result,
        ]);
    }

    public function update($id, UpdateNoticeRequest $request)
    {
        $validated = $request->validated();
        $result = $this->systemNoticeService->updateNotice($id, $validated);
        return response()->json([
            'message' => "更新系统公告 {$id}",
            'data' => $result,
        ]);
    }
}
