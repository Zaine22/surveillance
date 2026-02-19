<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

abstract class BaseFilterService
{
    protected function applyFilters(
        Builder $query,
        array $filters,
        array $searchableColumns = [],
        bool $hasStatus = true,
        string $dateColumn = 'created_at'
    ): LengthAwarePaginator {

        $page            = $filters['page'] ?? 1;
        $perPage         = $filters['per_page'] ?? 15;
        $search          = $filters['search'] ?? null;
        $status          = $filters['status'] ?? null;
        $analysisResult  = $filters['analysis_result'] ?? null;
        $range           = $filters['range'] ?? null;
        $fromDate        = $filters['from_date'] ?? null;
        $toDate          = $filters['to_date'] ?? null;
        $sortBy          = $filters['sort_by'] ?? 'created_at';
        $sortOrder       = strtolower($filters['sort_order'] ?? 'desc');

        if ($search && !empty($searchableColumns)) {
            $query->where(function ($q) use ($search, $searchableColumns) {
                foreach ($searchableColumns as $column) {
                    $q->orWhereRaw(
                        "LOWER($column) LIKE ?",
                        ['%' . strtolower($search) . '%']
                    );
                }
            });
        }

        if ($analysisResult) {
            $query->whereRaw(
                "LOWER(analysis_result) LIKE ?",
                ['%' . strtolower($analysisResult) . '%']
            );
        }

        if ($hasStatus && $status) {
            is_array($status)
                ? $query->whereIn('status', $status)
                : $query->where('status', $status);
        }

        if ($range) {
            $now = Carbon::now();

            switch ($range) {
                case 'one_week':
                    $query->whereBetween($dateColumn, [
                        $now->copy()->subWeek(),
                        $now
                    ]);
                    break;

                case 'one_month':
                    $query->whereBetween($dateColumn, [
                        $now->copy()->subMonth(),
                        $now
                    ]);
                    break;

                case 'one_year':
                    $query->whereBetween($dateColumn, [
                        $now->copy()->subYear(),
                        $now
                    ]);
                    break;
            }
        }
        if ($fromDate && $toDate) {
            $query->whereBetween($dateColumn, [
                Carbon::parse($fromDate)->startOfDay(),
                Carbon::parse($toDate)->endOfDay()
            ]);
        }

        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        if (!in_array($sortBy, $this->getAllowedSortColumns())) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    protected function getAllowedSortColumns(): array
    {
        return [
            'created_at',
            'updated_at',
            'status',
            'name',
            'ai_score',
        ];
    }
}
