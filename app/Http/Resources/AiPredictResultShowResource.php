<?php
namespace App\Http\Resources;

use App\Http\Resources\AiPredictResultItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiPredictResultShowResource extends JsonResource
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
            'ai_model_task_id'   => $this->ai_model_task_id,
            'lexicon_id'         => $this->lexicon_id,
            'keywords'           => $this->keywords,
            'ai_score'           => $this->ai_score,
            'analysis_result'    => $this->analysis_result,
            'review_status'      => $this->review_status,
            'audit_status'       => $this->audit_status,
            'ai_analysis_result' => $this->ai_analysis_result,
            'ai_analysis_detail' => $this->ai_analysis_detail,

            'created_at'         => $this->created_at,
            'items'              => AiPredictResultItemResource::collection(
                $this->whenLoaded('items')
            ),
            'case_management'    => $this->whenLoaded('caseManagement', function () {
                return new CaseManagementResource($this->caseManagement);
            }),
        ];
    }
}
