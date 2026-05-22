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
            'resutl_file'     => $this->result_file
                ? basename(parse_url($this->result_file, PHP_URL_PATH))
                : null,
            'result_file_url' => $this->getResultFileUrl($this->result_file),
            'status'          => $this->mapStatus($this->status),
            'crawl_location'  => $this->crawl_location,
            'error_message'   => $this->error_message,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }

    private function getResultFileName(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $urlPath = parse_url($path, PHP_URL_PATH) ?: $path;

        return basename($urlPath);
    }

    private function getResultFileUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $fileName = $this->getResultFileName($path);

        if (empty($fileName)) {
            return null;
        }

    return rtrim(config('services.task_files.base_url'), '/') . '/' . $fileName;
    }

    private function mapStatus(string $status): string
    {
        return match($status) {
            'crawling', 'syncing' => 'running',
            'error' => 'failed',
            'synced' => 'completed',
            'pending' => 'paused',
            default => $status,
        };
    }
}
