<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrawlerTaskItemsIndexResource extends JsonResource
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
            'keywords'        => $this->keywords,
            'crawler_machine' => $this->crawler_machine,
            'resutl_file'     => $this->result_file,
            'status'          => $this->status,
            'crawl_location'  => $this->crawl_location,
            'error_message'   => $this->error_message,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
