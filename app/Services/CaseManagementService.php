<?php

namespace App\Services;

use App\Models\CaseFeedback;

class CaseManagementService
{
    public function createCaseFeedback(array $data): CaseFeedback
    {
        $case = CaseFeedback::updateOrCreate(
            ['case_id' => $data['case_id']],
            [
                'url' => $data['url'],
                'is_illegal' => $data['is_ilLegal'],
                'legal_basis' => $data['legal_basis'] ?? null,
                'reason' => $data['reason'] ?? null,
            ]
        );

        return $case;
    }
}
