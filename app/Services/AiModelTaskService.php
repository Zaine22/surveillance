<?php

namespace App\Services;

use App\Models\AiModelTask;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AiModelTaskService extends BaseFilterService
{
    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = AiModelTask::with('crawlerTaskItem');

        if (! empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(file_name) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('crawlerTaskItem', function ($item) use ($search) {
                        $item->whereRaw('LOWER(keywords) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(result_file) LIKE ?', ["%{$search}%"]);
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $this->applyFilters(
            $query,
            $filters,
            [],
            false,
            'updated_at'
        );
    }

    public function getLatest(int $limit = 10): Collection
    {
        return AiModelTask::with('crawlerTaskItem')
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    protected function getAllowedSortColumns(): array
    {
        return [
            'created_at',
            'updated_at',
            'file_name',
            'status',
        ];
    }
}
