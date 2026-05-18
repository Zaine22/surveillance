<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeatureCode\GetAllFeatureCodeRequest;
use App\Http\Requests\FeatureCode\StoreFeatureCodeRequest;
use App\Http\Requests\FeatureCode\UpdateFeatureCodeRequest;
use App\Http\Resources\FeatureCodeResource;
use App\Services\FeatureCodeService;

class FeatureCodeController extends Controller
{
    public function __construct(
        private readonly FeatureCodeService $featureCodeService
    ) {}

    public function index(GetAllFeatureCodeRequest $request)
    {
        $validated = $request->validated();

        $featureCodes = $this->featureCodeService->getAllFeatureCodes($validated);

        return response()->json([
            'message' => '特徵碼列表',
            'data'    => $featureCodes,
        ]);
    }

    public function show($id)
    {
        try {
            $result = $this->featureCodeService->getFeatureCodeById($id);

            return response()->json([
                'message' => '显示特徵碼',
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve feature code',
            ], 404);
        }
    }

    public function store(StoreFeatureCodeRequest $request)
    {
        $validated = $request->validated();

        try {
            $result = $this->featureCodeService->createFeatureCode($validated);

            return response()->json([
                'message' => '特徵碼创建成功',
                'data'    => new FeatureCodeResource($result),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create feature code',
            ], 500);
        }
    }

    public function update($id, UpdateFeatureCodeRequest $request)
    {
        $validated = $request->validated();

        try {
            $result = $this->featureCodeService->updateFeatureCode($id, $validated);

            if (! $result) {
                return response()->json([
                    'error' => 'Feature code not found',
                ], 404);
            }

            return response()->json([
                'message' => "更新特徵碼 {$id}",
                'data' => new FeatureCodeResource($result),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update feature code',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $result = $this->featureCodeService->deleteFeatureCode($id);

            if (! $result) {
                return response()->json([
                    'error' => 'Feature code not found',
                ], 404);
            }

            return response()->json([
                'message' => '特徵碼删除成功',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete feature code',
            ], 500);
        }
    }
}
