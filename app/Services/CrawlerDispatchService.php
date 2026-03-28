<?php
namespace App\Services;

use App\Models\CrawlerTaskItem;
use Illuminate\Support\Facades\Redis;

class CrawlerDispatchService
{
    protected string $stream = 'crawler:task:stream';

    public function dispatch(CrawlerTaskItem $item): void
    {
        $domain = $this->extractDomain($item->crawl_location);

        $type = $domain === 'google.com'
            ? 'google_discovery_batch'
            : 'patrol';

        Redis::xadd(
            $this->stream,
            '*',
            [
                'task_item_id'   => (string) $item->id,
                'keywords'       => (string) $item->keywords,
                'crawl_location' => (string) $item->crawl_location,
                'type'           => $type,
            ]
        );
    }

    public function dispatchPauseItems(CrawlerTaskItem $item): void
    {

        Redis::xadd(
            $this->stream,
            '*',
            [
                'task_item_id' => (string) $item->id,
                'type'         => 'non_patrol',
            ]
        );
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
