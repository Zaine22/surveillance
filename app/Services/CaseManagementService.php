<?php
namespace App\Services;

use Illuminate\Support\Str;
use App\Models\CaseFeedback;
use App\Models\CaseManagement;
use App\Models\AiPredictResult;
use App\Models\CaseManagementItem;
use Illuminate\Support\Facades\DB;

class CaseManagementService
{
    public function __construct()
    {}

    public function createFromPredictResult(AiPredictResult $result): CaseManagement
    {
        return DB::transaction(function () use ($result) {

            $case = CaseManagement::create([
                'id'                   => (string) Str::uuid(),
                'ai_predict_result_id' => $result->id,
                'keywords'             => $result->keywords,
                'status'               => 'created',
            ]);

            foreach ($result->items as $item) {
                CaseManagementItem::create([
                    'id'                 => (string) Str::uuid(),
                    'case_management_id' => $case->id,
                    'media_url'          => $item->media_url,
                    'crawler_page_url'   => $item->crawler_page_url,
                    'ai_result'          => $item->ai_result,
                    'status'             => $item->status,
                ]);
            }

            return $case;
        });

        function createCaseFeedback(array $data): CaseFeedback
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

        function createExternalCase(array $data): CaseManagementItem
        {
            $external_case_id = 'EXT-' . strtoupper(uniqid());
            $case             = CaseManagement::create([
                'external_case_no' => $external_case_id,
            ]);

            $caseItem = CaseManagementItem::create([
                'case_management_id' => $case->id,
                'crawler_page_url'   => $data['url'],
                'status'             => 'valid',
                'reason'             => $data['leakReason'],
            ]);

            return $caseItem;
        }
    }
}
