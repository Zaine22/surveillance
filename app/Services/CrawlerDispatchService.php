<?php
namespace App\Services;

use App\Models\CrawlerTaskItem;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class CrawlerDispatchService
{
    protected string $stream = 'crawler:task:stream';

    public function __construct(
        protected ?CrawlerApiClient $apiClient = null
    ) {}

    /**
     * Original dispatch method using direct Redis connection
     * Used by existing crawler system
     */
    public function dispatch(CrawlerTaskItem $item): void
    {
        $domain = $this->extractDomain($item->crawl_location);

        $type = $domain === 'google.com'
            ? 'google_discovery_batch'
            : 'patrol';

        Redis::connection('crawler')->xadd(
            $this->stream,
            '*',
            [
                'task_item_id'   => (string) $item->id,
                'keywords'       => json_encode($item->keywords, JSON_UNESCAPED_UNICODE),
                'crawl_location' => (string) $item->crawl_location,
                'type'           => $type,
            ]
        );
    }

    /**
     * Original dispatchPauseItems method using direct Redis connection
     * Used by existing crawler system
     */
    public function dispatchPauseItems(CrawlerTaskItem $item): void
    {
        Redis::connection('crawler')->xadd(
            $this->stream,
            '*',
            [
                'task_item_id' => (string) $item->id,
                'type'         => 'non_patrol',
            ]
        );
    }

    /**
     * NEW: Dispatch via API Gateway (for surveillance app)
     * Use this method when you want to use the HTTPS API instead of direct Redis
     */
    public function dispatchViaApi(CrawlerTaskItem $item): void
    {
        if (!$this->apiClient) {
            $this->apiClient = app(CrawlerApiClient::class);
        }

        $domain = $this->extractDomain($item->crawl_location);

        $type = $domain === 'google.com'
            ? 'google_discovery_batch'
            : 'patrol';

        try {
            $this->apiClient->dispatchTask([
                'task_item_id'   => (string) $item->id,
                'keywords'       => $item->keywords,
                'crawl_location' => (string) $item->crawl_location,
                'type'           => $type,
            ]);

            Log::info('Crawler task dispatched via API', [
                'task_item_id' => $item->id,
                'type'         => $type,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to dispatch crawler task via API', [
                'task_item_id' => $item->id,
                'error'        => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * NEW: Dispatch pause items via API Gateway (for surveillance app)
     * Use this method when you want to use the HTTPS API instead of direct Redis
     */
    public function dispatchPauseItemsViaApi(CrawlerTaskItem $item): void
    {
        if (!$this->apiClient) {
            $this->apiClient = app(CrawlerApiClient::class);
        }

        try {
            $this->apiClient->dispatchTask([
                'task_item_id' => (string) $item->id,
                'keywords'     => [],
                'crawl_location' => '',
                'type'         => 'non_patrol',
            ]);

            Log::info('Pause item dispatched via API', [
                'task_item_id' => $item->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to dispatch pause item via API', [
                'task_item_id' => $item->id,
                'error'        => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function extractDomain(string $url): string
    {
        if (! preg_match('#^https?://#', $url)) {
            $url = 'http://' . $url;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return '';
        }

        return preg_replace('/^www\./', '', $host);
    }
}
