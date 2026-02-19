<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiPredictResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
           return [
            'id'                 => $this->id,
            'file_name'          => optional($this->aiModelTask)->file_name,
            'keywords'           => $this->keywords,
            'ai_score'           => $this->ai_score,
            'analysis_result'    => $this->analysis_result,
            'review_status'      => $this->review_status,
            'audit_status'       => $this->audit_status,
            'ai_analysis_result' => $this->ai_analysis_result,
            'created_at'         => $this->created_at,
        ];
    }
}
