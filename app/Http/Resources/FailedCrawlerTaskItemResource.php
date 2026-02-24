<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FailedCrawlerTaskItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'task_id'         => $this->task_id,
            'keywords'        => $this->keywords,
            'crawl_location'  => $this->crawl_location,
            'status'          => $this->status,
            'crawler_machine' => $this->crawler_machine,
            'error_message'   => $this->error_message,
            'last_updated'    => optional($this->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}