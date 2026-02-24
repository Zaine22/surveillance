<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskManagement\CrawlerTaskIndexRequest;
use App\Http\Requests\TaskManagement\UpdateCrawlerTaskStatusRequest;
use App\Http\Resources\CrawlerTaskIndexResource;
use App\Http\Resources\FailedCrawlerTaskItemResource;
use App\Models\CrawlerTask;
use App\Services\CrawlerTaskService;

class CrawlerTaskController extends Controller
{
    public function __construct(
        protected CrawlerTaskService $crawlerTaskService
    ) {}

    public function index(CrawlerTaskIndexRequest $request)
    {
        $filters = $request->validated();

        $tasks = $this->crawlerTaskService->getAllTasks($filters);

        // $summary = $this->crawlerTaskService->getSummary($filters);

        // return response()->json([
        //     'total_tasks' => (int) ($summary->total_tasks ?? 0),
        //     'total_pending'     => (int) ($summary->pending ?? 0),
        //     'total_processing'     => (int) ($summary->processing ?? 0),
        //     'total_completed'   => (int) ($summary->completed ?? 0),
        //     'total_error'      => (int) ($summary->error ?? 0),
        //     'total_paused'      => (int) ($summary->paused ?? 0),
        //     'total_deleted'      => (int) ($summary->deleted ?? 0),

        //     'data' => CrawlerTaskIndexResource::collection($tasks->items()),
        // ]);

        $summary = $this->crawlerTaskService->getTaskItemSummary($filters);

        return response()->json([
            'total_tasks'    => (int) ($summary->total_tasks ?? 0),
            'total_pending'  => (int) ($summary->total_pending ?? 0),
            'total_crawling' => (int) ($summary->total_crawling ?? 0),
            'total_syncing'  => (int) ($summary->total_syncing ?? 0),
            'total_synced'   => (int) ($summary->total_synced ?? 0),
            'total_error'    => (int) ($summary->total_error ?? 0),
            'data'           => CrawlerTaskIndexResource::collection($tasks->items()),
        ]);
    }

    public function updateStatus(
        UpdateCrawlerTaskStatusRequest $request,
        CrawlerTask $task
    ) {
        try {
            $message = $this->crawlerTaskService
                ->updateExecutionStatus($task, $request->action);

            return response()->json([
                'message' => $message,
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function failedTasks(CrawlerTask $task)
    {
        $failedTasks = $this->crawlerTaskService->getFailedTasks($task);

        return response()->json([
            'data' => FailedCrawlerTaskItemResource::collection($failedTasks),
        ]);
    }

}