<?php

namespace App\Services;

use App\Models\CaseFeedback;
use App\Models\CaseManagement;
use App\Models\CaseManagementItem;

class CaseManagementService
{
    public function createCaseFeedback(array $data): CaseFeedback
    {
        $case = CaseFeedback::updateOrCreate(
            ['case_id' => $data['case_id']],
            [
                'url' => $data['url'],
                'is_illegal' => $data['is_illegal'],
                'legal_basis' => $data['legal_basis'] ?? null,
                'reason' => $data['reason'] ?? null,
            ]
        );

        return $case;
    }

    public function createExternalCase(array $data): CaseManagementItem
    {
        $external_case_id = 'EXT-'.strtoupper(uniqid());
        $case = CaseManagement::create([
            'external_case_no' => $external_case_id,
        ]);

        $caseItem = CaseManagementItem::create([
            'case_management_id' => $case->id,
            'crawler_page_url' => $data['url'],
            'status' => 'valid',
            'reason' => $data['leakReason'],
        ]);

        return $caseItem;
    }
}
