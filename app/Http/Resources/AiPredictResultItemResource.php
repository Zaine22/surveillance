<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiPredictResultItemResource extends JsonResource
{

    public function validationData(): array
    {
        $data = parent::validationData();

        if (isset($data['review_status'])) {
            $map = [
                'approved' => 'reviewed',
                'pending'  => 'pending',
                'rejected' => 'rejected',
            ];

            $data['review_status'] = $map[$data['review_status']] ?? $data['review_status'];
        }

        return $data;
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'media_url'        => $this->media_url,
            'crawler_page_url' => $this->crawler_page_url,
            'ai_result'        => $this->ai_result,
            'status'           => $this->status,
            'reason'           => $this->reason,
            'other_reason'     => $this->other_reason,
            'ai_score'         => $this->ai_score,
            'keywords'         => $this->keywords,
            'created_at'       => $this->created_at,
        ];
    }
}
