<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseManagementIndexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusMap = [
            'pending_notification' => '待通知性影像中心',
            'notified'             => '已通知性影像中心',
            'case_established'     => '案件已建立',
            'case_not_established' => '案件不成立',
            'tracking_completed'   => '案件已完成擷圖追縱',
            'external_pending'     => '外部成案待建立',
        ];
        return [
            'id'               => $this->id,
            'internal_case_no' => $this->internal_case_no,
            'external_case_no' => $this->external_case_no,
            'keywords'         => $this->keywords,
            'status'           => $statusMap[$this->status] ?? $this->status,
            'comment'          => $this->comment,
            'file_name'        => optional($this->aiPredictResult?->aiModelTask)->file_name,
            'created_at'       => $this->created_at,
            'due_date' => optional($this->items->first())->due_date,
            'lexicon_id'       => $this->aiPredictResult?->lexicon_id,
        ];
    }
}
