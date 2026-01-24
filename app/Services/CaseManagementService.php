<?php

namespace App\Services;

use App\Models\AiPredictResult;
use App\Models\CaseManagement;
use App\Models\CaseManagementItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CaseManagementService
{
    public function __construct() {}

    public function createFromPredictResult(AiPredictResult $result): CaseManagement
    {
        return DB::transaction(function () use ($result) {

            $case = CaseManagement::create([
                'id' => (string) Str::uuid(),
                'ai_predict_result_id' => $result->id,
                'keywords' => $result->keywords,
                'status' => 'created',
            ]);

            foreach ($result->items as $item) {
                CaseManagementItem::create([
                    'id' => (string) Str::uuid(),
                    'case_management_id' => $case->id,
                    'media_url' => $item->media_url,
                    'crawler_page_url' => $item->crawler_page_url,
                    'ai_result' => $item->ai_result,
                    'status' => $item->status,
                ]);
            }

            return $case;
        });
    }
}
