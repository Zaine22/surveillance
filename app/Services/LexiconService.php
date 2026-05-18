<?php
namespace App\Services;

use App\Models\CaseManagement;
use App\Models\Lexicon;
use App\Models\LexiconKeyword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LexiconService extends BaseFilterService
{
    // public function getAllLexicons(array $filters): LengthAwarePaginator
    // {
    //     $page = $filters['page'] ?? 1;
    //     $perPage = $filters['per_page'] ?? 15;
    //     $search = $filters['search'] ?? null;
    //     $status = $filters['status'] ?? null;

    //     $query = Lexicon::with('keywords')
    //         ->orderBy('created_at', 'desc');

    //     if ($search) {
    //         $query->where('name', 'like', "%{$search}%");
    //     }

    //     if ($status) {
    //         $query->where('status', $status);
    //     }

    //     return $query->paginate($perPage, ['*'], 'page', $page);

    // }
    public function getAllLexicons(array $filters)
    {
        $query = Lexicon::with('keywords');
        $query = Lexicon::query()
            ->with('keywords')
            ->withSum([
                'keywords as crawl_hit_count' => function ($q) {
                    $q->where('status', 'enabled');
                },
            ], 'crawl_hit_count')
            ->withSum([
                'keywords as case_count' => function ($q) {
                    $q->where('status', 'enabled');
                },
            ], 'case_count');

        // Apply custom sorting for name field by default
        $sortBy = $filters['sort_by'] ?? 'name';
        if ($sortBy === 'name') {
            $sortOrder = strtolower($filters['sort_order'] ?? 'asc');
            $direction = $sortOrder === 'desc' ? 'DESC' : 'ASC';

            // Custom sorting: Chinese by stroke count, numbers (small to large), then English a-z
            $query->orderByRaw("
                CASE
                    WHEN name REGEXP '^[0-9]' THEN 2
                    WHEN name REGEXP '^[a-zA-Z]' THEN 3
                    ELSE 1
                END {$direction},
                CASE
                    WHEN name REGEXP '^[0-9]' THEN CAST(name AS UNSIGNED)
                    ELSE 0
                END {$direction},
                LOWER(name) {$direction}
            ");

            // Remove sort_by from filters to prevent duplicate sorting in applyFilters
            unset($filters['sort_by']);
            unset($filters['sort_order']);
        }

        return $this->applyFilters(
            $query,
            $filters,
            ['name', 'remark'],
            true
        );
    }

    public function getLexiconById(string $id): ?Lexicon
    {
        return Lexicon::with('keywords')->withSum([
            'keywords as crawl_hit_count' => function ($q) {
                $q->where('status', 'enabled');
            },
        ], 'crawl_hit_count')->find($id);
    }

    // public function getLexiconById(string $id): ?Lexicon
    // {
    //     return Lexicon::with([
    //         'keywords' => function ($q) {
    //             $q->whereNull('parent_id');
    //         },
    //     ])
    //         ->withSum([
    //             'keywords as crawl_hit_count' => function ($q) {
    //                 $q->where('status', 'enabled');
    //             },
    //         ], 'crawl_hit_count')
    //         ->find($id);
    // }

    public function createLexicon(array $data): Lexicon
    {
        return DB::transaction(function () use ($data) {

            $keywordGroups = $data['keywords'] ?? [];
            unset($data['keywords']);
            $caseManagementId = $data['case_management_id'] ?? null;
            $lexicon          = Lexicon::create($data);

            foreach ($keywordGroups as $group) {

                if (! is_array($group) || empty($group)) {
                    continue;
                }

                LexiconKeyword::create([
                    'lexicon_id'      => $lexicon->id,
                    'keywords'        => array_values(array_unique($group)),
                    'language'        => 'zh',
                    'crawl_hit_count' => 0,
                    'case_count'      => 0,
                    'status'          => 'enabled',
                ]);
            }

            if ($caseManagementId) {
                CaseManagement::where('id', $caseManagementId)->update([
                    'external_lexicon_id' => $lexicon->id,
                ]);
            }

            return $lexicon->load('keywords');
        });
    }

    public function updateLexicon(
        Lexicon $lexicon,
        array $data
    ): Lexicon {
        return DB::transaction(function () use ($lexicon, $data) {

            $keywordPayloads = $data['keywords'] ?? [];
            unset($data['keywords']);

            $lexicon->update($data);

            if (
                array_key_exists('status', $data)
                && $data['status'] === 'disabled'
            ) {
                LexiconKeyword::where('lexicon_id', $lexicon->id)
                    ->update(['status' => 'disabled']);
            }

            foreach ($keywordPayloads as $row) {

                $payload = [
                    'keywords' => array_values(array_unique($row['keywords'])),
                    'status'   => $row['status'],
                ];

                $keywordId = $row['id'] ?? null;

                if ($keywordId && Str::isUuid($keywordId)) {

                    LexiconKeyword::where('id', $keywordId)
                        ->where('lexicon_id', $lexicon->id)
                        ->update($payload);

                } else {

                    LexiconKeyword::create([
                        'lexicon_id'      => $lexicon->id,
                        'keywords'        => $payload['keywords'],
                        'status'          => $payload['status'],
                        'crawl_hit_count' => 0,
                        'case_count'      => 0,
                    ]);
                }
            }

            return $lexicon->load('keywords');
        });
    }

    public function deleteLexicon(Lexicon $lexicon): ?bool
    {
        return $lexicon->delete();
    }
}
