<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiPredictResultItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       return [
            'id' => $this->id,
            'media_url' => $this->media_url,
            'crawler_page_url' => $this->crawler_page_url,
            'ai_result' => $this->ai_result,
            'status' => $this->status,
            'reason' => $this->reason,
            'other_reason' => $this->other_reason,
            'ai_score' => $this->ai_score,
            'keywords' => $this->keywords,
            'created_at' => $this->created_at,
        ];
    }
}
