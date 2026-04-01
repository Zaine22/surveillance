<?php
namespace App\Services;

use App\Models\OperationLog;

class OperationLogService extends BaseFilterService
{
    public function getAllLogs(array $filters)
    {
        $query = OperationLog::query()->with('user');

        if (! empty($filters['department']) && $filters['department'] !== 'all') {
            $query->where('department', $filters['department']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        return $this->applyFilters(
            $query,
            $filters,
            ['operator_name'], // searchable column
            false,
            'created_at'
        );
    }

    protected function getAllowedSortColumns(): array
    {
        return [
            'created_at',
            'operator_name',
            'department',
            'action',
        ];
    }
}
