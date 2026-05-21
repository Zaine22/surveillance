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
        // Get total completed tasks
        $totalCompleted = \App\Models\AiModelTask::where('status', 'completed')->count();

        // Calculate average accuracy from AI predict results
        $averageAccuracy = \App\Models\AiPredictResult::whereHas('aiModelTask', function ($query) {
            $query->where('status', 'completed');
        })
            ->whereNotNull('ai_score')
            ->avg('ai_score');

        // Get health status from the most recent AI model
        $latestModel  = \App\Models\AiModel::latest('health_checked_at')->first();
        $healthStatus = $latestModel ? ($latestModel->health_status ?? 'Unknown') : 'Unknown';

        // Count total identified cases (abnormal results)
        $totalIdentified = \App\Models\AiPredictResult::where('ai_analysis_result', 'abnormal')
            ->whereHas('aiModelTask', function ($query) {
                $query->where('status', 'completed');
            })
            ->count();

        // return response()->json([
        //     'data' => [
        //         'average_accuracy' => 85.5,
        //         'health_status'    => 'Healthy',
        //         'total_identified' => \App\Models\AiModelTask::where('status', 'completed')->count(),
        //     ],
        // ]);
        return response()->json([
            'data' => [
                'average_accuracy'      => $averageAccuracy ? round($averageAccuracy, 2) : 0,
                'health_status'         => $healthStatus,
                'total_identified'      => $totalIdentified,
                'total_completed_tasks' => $totalCompleted,
            ],
        ]);
    }
}
