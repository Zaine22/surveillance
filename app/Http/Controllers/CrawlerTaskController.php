<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskManagement\CrawlerTaskIndexRequest;
use App\Http\Requests\TaskManagement\CrawlerTaskItemsRequest;
use App\Http\Requests\TaskManagement\UpdateCrawlerTaskStatusRequest;
use App\Http\Resources\CrawlerTaskIndexResource;
use App\Http\Resources\CrawlerTaskItemsIndexResource;
use App\Http\Resources\CrawlerTaskShowResource;
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
    public function show(
        CrawlerTask $crawlerTask,
        CrawlerTaskItemsRequest $request
    ) {
        $filters = $request->validated();
        $items   = $this->crawlerTaskService
            ->getAllTaskItems($crawlerTask, $filters);
        $crawlerTask->load('crawlerConfig.lexicon');
        $summary = $this->crawlerTaskService
            ->getSingleTaskItemSummary($crawlerTask);

        return response()->json([

            'summary' => [
                'total_tasks'    => (int) ($summary->total_tasks ?? 0),
                'total_pending'  => (int) ($summary->total_pending ?? 0),
                'total_crawling' => (int) ($summary->total_crawling ?? 0),
                'total_syncing'  => (int) ($summary->total_syncing ?? 0),
                'total_synced'   => (int) ($summary->total_synced ?? 0),
                'total_error'    => (int) ($summary->total_error ?? 0),
            ],
            'task'    => new CrawlerTaskShowResource($crawlerTask),
            'items'   => CrawlerTaskItemsIndexResource::collection($items),
            'meta'    => [
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
            ],
        ]);
    }

    public function getAllTaskItems(CrawlerTask $task, CrawlerTaskItemsRequest $request)
    {
        $filters = $request->validated();

        $items = $this->crawlerTaskService->getAllTaskItems($task, $filters);

        return response()->json([
            'data' => CrawlerTaskItemsIndexResource::collection($items->items()),
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
