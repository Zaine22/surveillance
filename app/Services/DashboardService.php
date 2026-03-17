<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getStats(array $params): array
    {
        [$from, $to, $range] = $this->resolveRange($params);

        $from = Carbon::parse($from)->startOfDay();
        $to   = Carbon::parse($to)->endOfDay();

        $cacheKey = "dashboard:all:{$range}:{$from->timestamp}:{$to->timestamp}";

        return Cache::remember($cacheKey, 5, function () use ($from, $to, $params) {
            return [
                'stats'                 => $this->compute($from, $to),
                'top_keywords'          => app(KeywordRankingService::class)
                    ->getRankingWithDate($from, $to, 5),
                'prejudgement_sources'  => $this->computeSources($from, $to),
                'casemanagment_sources' => $this->computeCaseDomainStats($from, $to),
                'system_announcements'  => $this->getSystemAnnouncements(),
            ];
        });
    }

    private function computeSources($from, $to): array
    {
        $items = DB::table('ai_predict_result_items as items')
            ->join('ai_predict_results as results', 'items.ai_predict_result_id', '=', 'results.id')
            ->join('ai_model_tasks as mt', 'results.ai_model_task_id', '=', 'mt.id')
            ->join('crawler_task_items as cti', 'mt.crawler_task_item_id', '=', 'cti.id')
            ->where('items.ai_result', 'abnormal')
            ->whereBetween('items.created_at', [$from, $to])
            ->select('cti.crawl_location')
            ->get();

        $sourceCounts = [];
        $total        = 0;

        foreach ($items as $item) {

            $url = $item->crawl_location ?? null;

            if (! $url) {
                continue;
            }

            $host = parse_url($url, PHP_URL_HOST) ?? 'unknown';

            if (! isset($sourceCounts[$host])) {
                $sourceCounts[$host] = 0;
            }

            $sourceCounts[$host]++;
            $total++;
        }

        if ($total === 0) {
            return [];
        }

        $result = [];

        foreach ($sourceCounts as $source => $count) {
            $result[] = [
                'source'     => $source,
                'count'      => $count,
                'percentage' => round(($count / $total) * 100, 2),
            ];
        }

        usort($result, fn($a, $b) => $b['count'] <=> $a['count']);

        return $result;
    }
    // private function computeCaseDomainStats($from, $to): array
    // {
    //     $rows = DB::table('case_management as cm')
    //         ->join('ai_predict_results as results', 'cm.ai_predict_result_id', '=', 'results.id')
    //         ->join('ai_model_tasks as mt', 'results.ai_model_task_id', '=', 'mt.id')
    //         ->join('crawler_task_items as cti', 'mt.crawler_task_item_id', '=', 'cti.id')
    //         ->whereBetween('cm.created_at', [$from, $to])
    //         ->selectRaw("
    //         SUBSTRING_INDEX(cti.crawl_location, '/', 3) as source,

    //         SUM(cm.status = 'created') as created,
    //         SUM(cm.status = 'notified') as notified,
    //         SUM(cm.status = 'moved_offline') as moved_offline,
    //         SUM(cm.status = 'auto_offline') as auto_offline,

    //         COUNT(*) as total
    //     ")
    //         ->groupBy('source')
    //         ->orderByDesc('total')
    //         ->get();

    //     $grandTotal = $rows->sum('total');

    //     return $rows->map(function ($row) use ($grandTotal) {
    //         return [
    //             'source'              => $row->source ?? 'unknown',

    //             'case_created'        => (int) $row->created,
    //             'notified'            => (int) $row->notified,
    //             'voluntarily_removed' => (int) $row->moved_offline,
    //             'migrated_removed'    => (int) $row->auto_offline,

    //             'total'               => (int) $row->total,

    //             'percentage'          => $grandTotal > 0
    //                 ? round(($row->total / $grandTotal) * 100, 2)
    //                 : 0,
    //         ];
    //     })->toArray();
    // }
    private function computeCaseDomainStats($from, $to): array
    {
        $rows = DB::table('case_management as cm')
            ->join('ai_predict_results as results', 'cm.ai_predict_result_id', '=', 'results.id')
            ->join('ai_model_tasks as mt', 'results.ai_model_task_id', '=', 'mt.id')
            ->join('crawler_task_items as cti', 'mt.crawler_task_item_id', '=', 'cti.id')
            ->whereIn('cm.status', [
                'created',
                'notified',
                'moved_offline',
                'auto_offline',
            ])
            ->whereBetween('cm.created_at', [$from, $to])
            ->selectRaw("
            SUBSTRING_INDEX(cti.crawl_location, '/', 3) as source,
            COUNT(*) as count
        ")
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        $total = $rows->sum('count');

        return $rows->map(function ($row) use ($total) {
            return [
                'source'     => $row->source ?? 'unknown',
                'count'      => (int) $row->count,
                'percentage' => $total > 0
                    ? round(($row->count / $total) * 100, 2)
                    : 0,
            ];
        })->toArray();
    }
    private function getSystemAnnouncements(): array
    {
        return DB::table('system_notices')
            ->where('status', 'published')
            ->orderByDesc('publish_date')
            ->limit(5)
            ->get([
                'id',
                'title',
                'content',
                'publish_date',
            ])
            ->map(function ($item) {
                return [
                    'id'           => $item->id,
                    'title'        => $item->title,
                    'content'      => $item->content,
                    'publish_date' => $item->publish_date,
                    'publisher'    => 'System',
                ];
            })
            ->toArray();
    }

    private function compute($from, $to): array
    {
        return [
            'total_tasks'              => $this->totalTasks($from, $to),
            'completed_tasks'          => $this->completedTasks($from, $to),
            'suspected_abnormal_cases' => $this->suspectedCases($from, $to),
            'pending_cases'            => $this->pendingCases($from, $to),
            'new_cases'                => $this->newCases($from, $to),
            'ai_accuracy_percent'      => $this->accuracy($from, $to),
        ];
    }

    private function totalTasks($from, $to): int
    {
        return DB::table('crawler_tasks')
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function completedTasks($from, $to): int
    {
        return DB::table('crawler_tasks')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function suspectedCases($from, $to): int
    {
        return DB::table('ai_predict_results')
            ->where('ai_analysis_result', 'abnormal')
            ->where('review_status', 'pending')
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function pendingCases($from, $to): int
    {
        return DB::table('case_management')
            ->where('status', 'pending')
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function newCases($from, $to): int
    {
        return DB::table('case_management')
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function accuracy($from, $to): float
    {
        $data = DB::table('ai_predict_result_items as items')
            ->whereBetween('items.created_at', [$from, $to])
            ->selectRaw("
                COUNT(*) as total,
                SUM(
                    CASE
                        WHEN items.ai_result = 'abnormal' AND items.status = 'valid' THEN 1
                        WHEN items.ai_result = 'normal' AND items.status = 'invalid' THEN 1
                        ELSE 0
                    END
                ) as correct
            ")
            ->first();

        if (! $data || $data->total == 0) {
            return 0;
        }

        return round(($data->correct / $data->total) * 100, 2);
    }

    private function resolveRange(array $params): array
    {
        if (! empty($params['from_date']) && ! empty($params['to_date'])) {
            return [
                Carbon::parse($params['from_date'])->startOfDay(),
                Carbon::parse($params['to_date'])->endOfDay(),
                'custom',
            ];
        }

        $range = $params['range'] ?? null;

        switch ($range) {
            case 'today':
                return [now()->startOfDay(), now()->endOfDay(), 'today'];
            case 'one_week':
                return [now()->subDays(7)->startOfDay(), now()->endOfDay(), 'one_week'];
            case 'one_month':
                return [now()->subDays(30)->startOfDay(), now()->endOfDay(), 'one_month'];
            case 'this_week':
                return [now()->startOfWeek(), now()->endOfWeek(), 'this_week'];
            case 'this_month':
                return [now()->startOfMonth(), now()->endOfMonth(), 'this_month'];
            case 'one_year':
                return [now()->subDays(365)->startOfDay(), now()->endOfDay(), 'one_year'];
        }

        return [now()->subDays(7)->startOfDay(), now()->endOfDay(), 'default'];
    }
}