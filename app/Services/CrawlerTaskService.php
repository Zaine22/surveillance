<?php

namespace App\Services;

use App\Models\CrawlerConfig;
use App\Models\CrawlerTask;
use App\Models\CrawlerTaskItem;
use App\Models\Lexicon;
use App\Services\CrawlerTaskItemService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CrawlerTaskService extends BaseFilterService
{
    public function __construct(
        protected CrawlerTaskItemService $itemService
    ) {}

    public function getAllTasks(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters);

        return $this->applyFilters($query, $filters, [], true);
    }

    public function createFromConfig(CrawlerConfig $config, Lexicon $lexicon): CrawlerTask
    {
        return DB::transaction(function () use ($config, $lexicon) {

            $task = CrawlerTask::create([
                'crawler_config_id' => $config->id,
                'lexicon_id' => $lexicon->id,
                'status' => 'pending',
            ]);

            $this->itemService->createFromTask($task, $config, $lexicon);

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
                'items as pending_count'   => fn($q) => $q->where('status', 'pending'),
                'items as crawling_count'   => fn($q) => $q->where('status', 'crawling'),
                'items as syncing_count' => fn($q) => $q->where('status', 'syncing'),
                'items as error_count'    => fn($q) => $q->where('status', 'error'),
                'items as synced_count'    => fn($q) => $q->where('status', 'synced'),
            ]);

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);

            $query->whereHas('crawlerConfig', function ($q) use ($search) {
                $q->whereRaw("LOWER(name) LIKE ?", ["%{$search}%"]);
            });
        }

        return $query;
    }
    public function getTaskItemSummary(array $filters)
{
    $itemQuery = CrawlerTaskItem::query()
        ->whereHas('task', function ($task) use ($filters) {

            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);

                $task->whereHas('crawlerConfig', function ($q) use ($search) {
                    $q->whereRaw("LOWER(name) LIKE ?", ["%{$search}%"]);
                });
            }

            if (!empty($filters['status'])) {
                $task->where('status', $filters['status']);
            }

            if (!empty($filters['from_date'])) {
                $task->whereDate('created_at', '>=', $filters['from_date']);
            }

            if (!empty($filters['to_date'])) {
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

    if (!empty($filters['search'])) {
        $search = strtolower($filters['search']);

        $query->whereHas('crawlerConfig', function ($q) use ($search) {
            $q->whereRaw("LOWER(name) LIKE ?", ["%{$search}%"]);
        });
    }

    if (!empty($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    if (!empty($filters['from_date'])) {
        $query->whereDate('created_at', '>=', $filters['from_date']);
    }

    if (!empty($filters['to_date'])) {
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

}
