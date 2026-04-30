<?php
namespace App\Services;

use App\Jobs\ProcessExternalCaseJob;
use App\Jobs\SyncCaseScreenshotJob;
use App\Models\AiPredictResult;
use App\Models\CaseFeedback;
use App\Models\CaseManagement;
use App\Models\CaseManagementItem;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CaseManagementService extends BaseFilterService
{
    public function __construct()
    {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = CaseManagement::with([
            'aiPredictResult.aiModelTask',
        ]);
        if (! empty($filters['search'])) {
            $search = strtolower($filters['search']);

            $query->where(function ($q) use ($search) {

                $q->orWhereRaw(
                    'LOWER(internal_case_no) LIKE ?',
                    ["%{$search}%"]
                )
                    ->orWhereRaw(
                        'LOWER(external_case_no) LIKE ?',
                        ["%{$search}%"]
                    )
                    ->orWhereRaw(
                        'LOWER(keywords) LIKE ?',
                        ["%{$search}%"]
                    )
                    ->orWhereHas('aiPredictResult.aiModelTask', function ($task) use ($search) {
                        $task->whereRaw(
                            'LOWER(file_name) LIKE ?',
                            ["%{$search}%"]
                        );
                    });
            });
        }

        if (! empty($filters['status'])) {
            if ($filters['status'] === '全部') {
                unset($filters['status']);
            } else {
                // $statusMap = [
                //     '立案' => 'pending',
                //     '成案' => 'created',
                //     '待截圖' => 'notified',
                //     '截圖完成' => 'moved_offline',
                //     '不成案' => 'auto_offline',
                // ];
                $statusMap = [
                    '待通知性影像中心'  => 'pending_notification',
                    '已通知性影像中心'  => 'notified',
                    '案件已建立'     => 'case_established',
                    '案件不成立'     => 'case_not_established',
                    '案件已完成擷圖追縱' => 'tracking_completed',
                    '外部成案待建立'   => 'external_pending',
                ];

                if (isset($statusMap[$filters['status']])) {
                    $filters['status'] = $statusMap[$filters['status']];
                }
            }
        }

        if (! empty($filters['range'])) {
            $dateRange = $filters['range'];
            $now       = now();

            switch ($dateRange) {
                case '一週':
                    $query->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
                    break;
                case '一個月':
                    $query->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
                    break;
                case '一年':
                    $query->whereBetween('created_at', [$now->copy()->startOfYear(), $now->copy()->endOfYear()]);
                    break;
                case '自行選擇範圍':
                    if (! empty($filters['from_date']) && ! empty($filters['to_date'])) {
                        $query->whereBetween('created_at', [
                            \Carbon\Carbon::parse($filters['from_date'])->startOfDay(),
                            \Carbon\Carbon::parse($filters['to_date'])->endOfDay(),
                        ]);
                    }
                    break;
            }
        }

        return $this->applyFilters(
            $query,
            $filters,
            [],
            true,
            'updated_at'
        );
    }

    protected function getAllowedSortColumns(): array
    {
        return [
            'created_at',
            'status',
            'internal_case_no',
            'external_case_no',
        ];
    }

    public function findById(string $id): CaseManagement
    {
        return CaseManagement::query()
            ->with([
                'items',
            ])
            ->findOrFail($id);
    }

    public function createFromPredictResult(AiPredictResult $result): CaseManagement
    {
        return DB::transaction(function () use ($result) {

            $case = CaseManagement::create([
                'id'                   => (string) Str::uuid(),
                'ai_predict_result_id' => $result->id,
                'keywords'             => $result->keywords,
                'internal_case_no'     => 'INT-' . strtoupper(uniqid()),
                'status'               => 'pending_notification',
            ]);

            // foreach ($result->items as $item) {
            //     CaseManagementItem::create([
            //         'id' => (string) Str::uuid(),
            //         'case_management_id' => $case->id,
            //         'media_url' => $item->media_url,
            //         'crawler_page_url' => $item->crawler_page_url,
            //         'ai_result' => $item->ai_result,
            //         'status' => $item->status,
            //     ]);
            // }

            return $case;
        });
    }

    public function createCaseFeedback(array $data): CaseFeedback
    {
        $case = CaseFeedback::updateOrCreate(
            ['case_id' => $data['case_id']],
            [
                'url'         => $data['url'],
                'is_illegal'  => $data['is_illegal'],
                'legal_basis' => $data['legal_basis'] ?? null,
                'reason'      => $data['reason'] ?? null,
            ]
        );

        return $case;
    }

    public function createExternalCase(array $data): CaseManagementItem
    {
        $external_case_id = $data['case_id'];
        $case             = CaseManagement::create([
            'id'               => (string) Str::uuid(),
            'external_case_no' => $external_case_id,
            'status'           => 'external_pending',
        ]);

        $caseItem = CaseManagementItem::create([
            'case_management_id' => $case->id,
            'crawler_page_url'   => $data['url'],
            'status'             => 'valid',
            'reason'             => $data['leakReason'],
            'issue_date'         => $data['issue_date'] ?? null,
            'due_date'           => $data['due_date'] ?? null,
        ]);

        $issueDate = ! empty($data['issue_date'])
            ? Carbon::parse($data['issue_date'])
            : null;

        if ($issueDate && $issueDate->isFuture()) {
            \Illuminate\Support\Facades\Log::info('Dispatching ProcessExternalCaseJob with DELAY', [
                'case_item_id' => $caseItem->id,
                'issue_date'   => $issueDate->toDateTimeString(),
            ]);

            ProcessExternalCaseJob::dispatch($caseItem)->delay($issueDate);
        } else {
            \Illuminate\Support\Facades\Log::info('Dispatching ProcessExternalCaseJob IMMEDIATELY (Sync)', [
                'case_item_id' => $caseItem->id,
            ]);

            ProcessExternalCaseJob::dispatch($caseItem);
        }

        return $caseItem;
    }

    public function updateExternalKeywords(array $data): CaseManagement
    {
        $case = CaseManagement::where('external_case_no', $data['case_id'])
            ->firstOrFail();

        $case->update([
            'keywords' => $data['keywords'],
        ]);

        return $case;
    }

    public function caseScreenShot(array $validated)
    {

        // $case = CaseManagementItem::where('crawler_page_url', $validated['url'])->firstOrFail();

        // $case->update([
        //     'issue_date' => $validated['issue_date'],
        //     'due_date'   => $validated['due_date'],
        // ]);

        // $response = Http::post(
        //     env('SCREENSHOT_URL'),
        //     [
        //         'case_management_id'      => $case->case_management_id,
        //         'case_management_item_id' => $case->id, // or item_id if you have it
        //         'url'                     => $case->crawler_page_url,
        //     ]
        // );

        // if ($response->failed()) {
        //     return response()->json([
        //         'message' => 'Case updated but crawler API failed',
        //         'error'   => $response->body(),
        //     ], 500);
        // }

        // return response()->json([
        //     'message' => 'Case updated successfully',
        //     'data'    => $case,
        // ]);

        $case = CaseManagement::where('external_case_no', $validated['case_id'])
            ->firstOrFail();

        $caseItem = CaseManagementItem::where('case_management_id', $case->id)
            ->where('crawler_page_url', $validated['url'])
            ->firstOrFail();

        $caseItem->update([
            'issue_date' => $validated['issue_date'],
            'due_date'   => $validated['due_date'],
        ]);

        $response = Http::post(
            env('SCREENSHOT_URL'),
            [
                'case_management_id'      => $case->id,
                'case_management_item_id' => $caseItem->id,
                'url'                     => $caseItem->crawler_page_url,
            ]
        );

        if ($response->failed()) {
            return [
                'message' => 'Case updated but crawler API failed',
                'error'   => $response->body(),
            ];
        }

        return [
            'message' => 'Case updated successfully',
            'data'    => $caseItem,
        ];
    }

    public function captureCaseScreenshot(string $id, array $data)
    {
        try {
            $caseItem = CaseManagementItem::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Case not found');
        }

        $caseItem->update([
            'media_url' => $data['media_url'],
            'status'    => $data['status'] ?? $caseItem->status,
        ]);

        $caseItem->case->update([
            'status' => 'tracking_completed',
        ]);

        if ($data['media_url']) {
            SyncCaseScreenshotJob::dispatch($caseItem);
        }

        return $caseItem;
    }

    public function getExternalCase(array $filters): LengthAwarePaginator
    {
        $query = CaseManagement::with(['items'])
            ->whereNotNull('external_case_no');

        if (! empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(external_case_no) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->whereRaw('LOWER(media_url) LIKE ?', ["%{$search}%"]);
                    })
                    ->orWhereHas('aiPredictResult.aiModelTask', function ($taskQuery) use ($search) {
                        $taskQuery->whereRaw('LOWER(file_name) LIKE ?', ["%{$search}%"]);
                    });
            });
        }

        if (! empty($filters['status'])) {
            if ($filters['status'] === '全部') {
                unset($filters['status']);
            } else {
                $statusMap = [
                    '待通知性影像中心'     => 'pending_notification',
                    '已通知性影像中心'     => 'notified',
                    '案件已建立(追縱下架中)' => 'case_established',
                    '案件不成立'        => 'case_not_established',
                    '案件已完成擷圖追縱'    => 'tracking_completed',
                    '外部成案待建立'      => 'external_pending',
                ];

                if (isset($statusMap[$filters['status']])) {
                    $filters['status'] = $statusMap[$filters['status']];
                }
            }
        }

        if (! empty($filters['dateRange'])) {
            $dateRange = $filters['dateRange'];
            $now       = now();

            switch ($dateRange) {
                case '一週':
                    $query->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
                    break;
                case '一個月':
                    $query->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
                    break;
                case '一年':
                    $query->whereBetween('created_at', [$now->copy()->startOfYear(), $now->copy()->endOfYear()]);
                    break;
                case '自行選擇範圍':
                    if (! empty($filters['from']) && ! empty($filters['to'])) {
                        $query->whereBetween('created_at', [
                            \Carbon\Carbon::parse($filters['from'])->startOfDay(),
                            \Carbon\Carbon::parse($filters['to'])->endOfDay(),
                        ]);
                    }
                    break;
            }
        }

        return $this->applyFilters(
            $query,
            $filters,
            [],
            true,
            'created_at'
        );
    }

    public static function allowedTransitions(): array
    {
        return [
            'pending_notification' => ['notified'],

            'notified'             => [
                'case_established',
                'case_not_established',
            ],

            'case_established'     => [
                'tracking_completed',
            ],

            'external_pending'     => [
                'case_established',
            ],
        ];
    }

    public function updateStatus(CaseManagement $case, string $newStatus): CaseManagement
    {
        $allowed = self::allowedTransitions()[$case->status] ?? [];

        if (! in_array($newStatus, $allowed)) {
            throw new \InvalidArgumentException("Invalid status transition: {$case->status} → {$newStatus}");
        }

        $case->update([
            'status' => $newStatus,
        ]);

        return $case;
    }

}
