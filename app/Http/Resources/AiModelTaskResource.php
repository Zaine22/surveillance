<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiModelTaskResource extends JsonResource
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
            'ai_model_id' => $this->ai_model_id,
            'crawler_task_item_id' => $this->crawler_task_item_id,
            'file_name' => $this->file_name,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'crawler_task_item' => new CrawlerTaskItemsIndexResource($this->whenLoaded('crawlerTaskItem')),
        ];
    }
}
