<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LexiconResourceForAll extends JsonResource
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
            'name' => $this->name,
            'remark' => $this->remark,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // 'keywords' => LexiconKeywordResource::collection(
            //     $this->whenLoaded('keywords')
            // ),
            'crawl_hit_count' => 3842,
            'case_count' => 3842,
        ];
    }
}
