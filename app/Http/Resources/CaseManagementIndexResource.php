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
        // dd($this->aiPredictResult->aiModelTask->crawlerTaskItem->lexicon->keywords);
        return [
            'id'               => $this->id,
            'internal_case_no' => $this->internal_case_no,
            'external_case_no' => $this->external_case_no,
            'keywords'         => $this->keywords,
            'status'           => $this->status,
            'comment'          => $this->comment,
            'file_name'        => optional($this->aiPredictResult?->aiModelTask)->file_name,
            'created_at'       => $this->created_at,
            'lexicon_id '      => $this->aiPredictResult->lexicon_id,
        ];
    }
}
