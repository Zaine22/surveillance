<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrawlerTaskIndexResource extends JsonResource
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
            'config_name' => $this->crawlerConfig?->name,
            'total_tasks' => $this->total_tasks ?? 0,
            'pending'     => $this->pending_count ?? 0,
            'crawling'     => $this->crawling_count ?? 0,
            'syncing'   => $this->syncing_count ?? 0,
            'synced'      => $this->synced_count ?? 0,
            'error'      => $this->error_count ?? 0,
            'execution_status' => $this->status,
            'last_updated' => optional($this->updated_at)->format('Y-m-d H:i:s')];
    }
}
