<?php
namespace App\Services;

use App\Models\CrawlerConfig;
use App\Models\CrawlerTask;
use App\Models\CrawlerTaskItem;
use App\Models\Lexicon;
use App\Services\CrawlerTaskItemService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CrawlerTaskService extends BaseFilterService
{
    public function __construct(
        protected CrawlerTaskItemService $itemService,
        protected CrawlerDispatchService $dispatchService,
    ) {}

    public function getAllTasks(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters);

        return $this->applyFilters($query, $filters, [], true);
    }

    public function getAllTaskItems(CrawlerTask $task, array $filters): LengthAwarePaginator
    {
        $query = CrawlerTaskItem::query()
            ->where('task_id', $task->id)

            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $search = strtolower($filters['search']);

                $q->whereRaw(
                    'LOWER(keywords) LIKE ?',
                    ["%{$search}%"]
                );
            });

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(10);
    }

    public function createFromConfig(CrawlerConfig $config, Lexicon $lexicon): CrawlerTask
    {
        return DB::transaction(function () use ($config, $lexicon) {

            $task = CrawlerTask::create([
                'crawler_config_id' => $config->id,
                'lexicon_id'        => $lexicon->id,
                'status'            => 'pending',
            ]);

            $this->itemService->createFromTask($task, $config, $lexicon);

            $task->update([
                'status' => 'processing',
            ]);
            return $task;
        });
    }

    public function refreshStatus(CrawlerTask $task): void
    {
        if ($task->items()->where('status', 'error')->exists()) {
            $task->update(['status' => 'error']);

            return;
        }

        if ($task->items()->whereNotIn('status', ['synced'])->exists()) {
            $task->update(['status' => 'processing']);

            return;
        }

        $task->update(['status' => 'completed']);
    }

    public function baseQuery(array $filters)
    {
        $query = CrawlerTask::with('crawlerConfig')
            ->withCount([
                'items as total_tasks',
                'items as pending_count'  => fn($q)  => $q->where('status', 'pending'),
                'items as crawling_count' => fn($q) => $q->where('status', 'crawling'),
                'items as syncing_count'  => fn($q)  => $q->where('status', 'syncing'),
                'items as error_count'    => fn($q)    => $q->where('status', 'error'),
                'items as synced_count'   => fn($q)   => $q->where('status', 'synced'),
            ]);

        if (! empty($filters['search'])) {
            $search = strtolower($filters['search']);

            $query->whereHas('crawlerConfig', function ($q) use ($search) {
                $q->whereRaw("LOWER(name) LIKE ?", ["%{$search}%"]);
            });
        }

        return $query;
    }
    public function getSingleTaskItemSummary(CrawlerTask $task)
    {
        return CrawlerTaskItem::where('task_id', $task->id)
            ->selectRaw("
            COUNT(*) as total_tasks,
            SUM(status = 'pending')  as total_pending,
            SUM(status = 'crawling') as total_crawling,
            SUM(status = 'syncing')  as total_syncing,
            SUM(status = 'synced')   as total_synced,
            SUM(status = 'error')    as total_error
        ")
            ->first();
    }
    public function getTaskItemSummary(array $filters)
    {
        if (! empty($filters['range'])) {
            $now = Carbon::now();

            switch ($filters['range']) {
                case 'one_week':
                    $filters['from_date'] = $now->copy()->subWeek()->startOfDay();
                    $filters['to_date']   = $now->copy()->endOfDay();
                    break;

                case 'one_month':
                    $filters['from_date'] = $now->copy()->subMonth()->startOfDay();
                    $filters['to_date']   = $now->copy()->endOfDay();
                    break;

                case 'one_year':
                    $filters['from_date'] = $now->copy()->subYear()->startOfDay();
                    $filters['to_date']   = $now->copy()->endOfDay();
                    break;
            }
        }
        $itemQuery = CrawlerTaskItem::query()
            ->whereHas('task', function ($task) use ($filters) {

                if (! empty($filters['search'])) {
                    $search = strtolower($filters['search']);

                    $task->whereHas('crawlerConfig', function ($q) use ($search) {
                        $q->whereRaw("LOWER(name) LIKE ?", ["%{$search}%"]);
                    });
                }

                if (! empty($filters['from_date'])) {
                    $task->whereDate('created_at', '>=', $filters['from_date']);
                }

                if (! empty($filters['to_date'])) {
                    $task->whereDate('created_at', '<=', $filters['to_date']);
                }
            });

        return $itemQuery
            ->selectRaw("
            COUNT(*) as total_tasks,
            SUM(status = 'pending')  as total_pending,
            SUM(status = 'crawling') as total_crawling,
            SUM(status = 'syncing')  as total_syncing,
            SUM(status = 'synced')   as total_synced,
            SUM(status = 'error')    as total_error
        ")
            ->first();
    }
    public function getSummary(array $filters)
    {
        $query = CrawlerTask::query();

        if (! empty($filters['search'])) {
            $search = strtolower($filters['search']);

            $query->whereHas('crawlerConfig', function ($q) use ($search) {
                $q->whereRaw("LOWER(name) LIKE ?", ["%{$search}%"]);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query
            ->selectRaw("
            COUNT(*) as total_tasks,
            SUM(status = 'pending') as pending,
            SUM(status = 'processing') as processing,
            SUM(status = 'completed') as completed,
            SUM(status = 'error') as error,
            SUM(status = 'paused') as paused,
            SUM(status = 'deleted') as deleted
        ")
            ->first();
    }

    public function updateExecutionStatus(CrawlerTask $task, string $action): string
    {
        if ($task->status === 'completed' && $action !== 'delete') {
            throw new \Exception('Completed task cannot be modified.');
        }

        switch ($action) {

            case 'pause':
                if ($task->status !== 'processing') {
                    throw new \Exception('Only processing tasks can be paused.');
                }

                $task->update(['status' => 'paused']);
                return 'Task paused successfully';

            case 'resume':
                if ($task->status !== 'paused') {
                    throw new \Exception('Only paused tasks can be resumed.');
                }

                $task->update(['status' => 'processing']);
                return 'Task resumed successfully';

            case 'delete':
                if ($task->status === 'processing') {
                    throw new \Exception('Cannot delete a running task.');
                }

                $task->update(['status' => 'deleted']);
                return 'Task deleted successfully';

            default:
                throw new \Exception('Invalid action.');
        }
    }

    public function getFailedTasks(CrawlerTask $task)
    {
        return $task->items()
            ->where('status', 'error')
            ->get();
    }

    public function start(CrawlerTask $task): array
    {
        if (! in_array($task->status, ['pending', 'paused'])) {
            return [
                'success' => false,
                'message' => 'Task cannot be started.',
                'status'  => $task->status,
            ];
        }

        DB::transaction(function () use ($task) {

            $items = $task->items()
                ->whereIn('status', ['pending', 'error'])
                ->get();

            foreach ($items as $item) {
                $this->dispatchService->dispatch($item);
                $item->update(['status' => 'crawling']);
            }

            $task->update(['status' => 'processing']);
        });

        return [
            'success' => true,
            'message' => 'Task started successfully',
            'status'  => 'processing',
        ];
    }
    public function pause(CrawlerTask $task): array
    {
        if ($task->status !== 'processing') {
            return [
                'success' => false,
                'message' => 'Only processing tasks can be paused.',
                'status'  => $task->status,
            ];
        }

        DB::transaction(function () use ($task) {

            $items = $task->items()
                ->where('status', 'crawling')
                ->get();

            foreach ($items as $item) {
                $this->dispatchService->dispatchPauseItems($item);
                $item->update(['status' => 'pending']);
            }

            $task->update(['status' => 'paused']);
        });

        return [
            'success' => true,
            'message' => 'Task paused successfully',
            'status'  => 'paused',
        ];
    }
    public function resume(CrawlerTask $task): array
    {
        if ($task->status !== 'paused') {
            return [
                'success' => false,
                'message' => 'Only paused tasks can be resumed.',
                'status'  => $task->status,
            ];
        }

        DB::transaction(function () use ($task) {

            $items = $task->items()
                ->whereIn('status', ['pending', 'error'])
                ->get();

            foreach ($items as $item) {
                $this->dispatchService->dispatch($item);
                $item->update(['status' => 'crawling']);
            }

            $task->update(['status' => 'processing']);
        });

        return [
            'success' => true,
            'message' => 'Task resumed successfully',
            'status'  => 'processing',
        ];
    }

    public function delete(CrawlerTask $task): array
    {
        if ($task->status === 'processing') {
            return [
                'success' => false,
                'message' => 'Cannot delete a running task.',
                'status'  => $task->status,
            ];
        }

        DB::transaction(function () use ($task) {

            foreach ($task->items as $item) {
                $this->dispatchService->dispatchPauseItems($item);
                $item->update(['status' => 'pending']);
            }

            $task->update(['status' => 'deleted']);
        });

        return [
            'success' => true,
            'message' => 'Task destroyed successfully',
            'status'  => 'deleted',
        ];
    }

}
