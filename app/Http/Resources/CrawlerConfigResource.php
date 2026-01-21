<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrawlerConfigResource extends JsonResource
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
            'sources' => $this->sources,
            'description' => $this->description,
            'frequency_code' => $this->frequency_code,
            'status' => $this->status,
            'lexicon' => new LexiconResource($this->whenLoaded('lexicon')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
