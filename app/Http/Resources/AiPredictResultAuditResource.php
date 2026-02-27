<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiPredictResultAuditResource extends JsonResource
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
            'decision'          => $this->decision,
            'file_name'         => $this->result?->aiModelTask?->file_name,
            'ai_predict_result_id' => $this->ai_predict_result_id,
            'valid_count'       => $this->valid_count,
            'invalid_count'     => $this->invalid_count,
            'auditor'           => $this->auditor->name ?? null,
            'created_at'        => $this->created_at,
        ];
    }
}
