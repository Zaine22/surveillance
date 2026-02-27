<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrawlerTaskShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'lexicon_id'  => $this->lexicon_id,
            'keywords'    => $this->lexicon?->keywords->pluck('keywords')->flatten()->unique()->values(),
            'source_list' => $this->crawlerConfig?->sources,
        ];
    }
}