<?php

namespace App\Http\Controllers;

use App\Http\Requests\AiModelTask\AiModelTaskIndexRequest;
use App\Http\Resources\AiModelTaskResource;
use App\Services\AiModelTaskService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AiModelTaskController extends Controller
{
    public function __construct(
        protected AiModelTaskService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(AiModelTaskIndexRequest $request): AnonymousResourceCollection
    {
        $tasks = $this->service->getAll($request->validated());

        return AiModelTaskResource::collection($tasks);
    }

    /**
     * Display the latest 10 updated tasks.
     */
    public function latest(): AnonymousResourceCollection
    {
        $tasks = $this->service->getLatest(10);

        return AiModelTaskResource::collection($tasks);
    }

    public function stats()
    {
        return response()->json([
            'data' => [
                'average_accuracy' => 98.5,
                'health_status' => 'Healthy',
                'total_identified' => \App\Models\AiModelTask::where('status', 'completed')->count(),
            ],
        ]);
    }
}
