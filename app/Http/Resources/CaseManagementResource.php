<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseManagementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'internal_case_no'  => $this->internal_case_no,
            'external_case_no'  => $this->external_case_no,
            'keywords'          => $this->keywords,
            'status'            => $this->status,
            'comment'           => $this->comment,
            'file_name'         => optional($this->aiPredictResult?->aiModelTask)->file_name,
            'created_at'        => $this->created_at,
        ];
    }
}
