<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AiHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiModelController extends Controller
{
    protected AiHealthService $service;

    public function __construct(AiHealthService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->service->getModels($request->all()),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $model = $this->service->getModelById($id);

        if (! $model) {
            return response()->json(['message' => 'Model not found'], 404);
        }

        return response()->json(['data' => $model]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => $this->service->getStats(),
        ]);
    }

    public function check(string $id): JsonResponse
    {
        $model = \App\Models\AiModel::findOrFail($id);

        $this->service->check($model);

        return response()->json([
            'message' => 'Health checked successfully',
        ]);
    }
}
