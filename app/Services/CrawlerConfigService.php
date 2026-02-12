<?php

namespace App\Services;

use App\Models\CrawlerConfig;
use App\Models\Lexicon;
use App\Services\CrawlerTaskService;
use App\Services\GlobalWhitelistService;
use Illuminate\Pagination\LengthAwarePaginator;

class CrawlerConfigService
{
    public function __construct(
        protected CrawlerTaskService $crawlerTaskService,
        protected GlobalWhitelistService $globalWhitelistService
    ) {}

    public function getAllConfigs(int $perPage = 15): LengthAwarePaginator
    {
        return CrawlerConfig::with('lexicon')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getConfigById(string $id): ?CrawlerConfig
    {
        return CrawlerConfig::with('lexicon')->find($id);
    }

    // public function createConfig(array $data): CrawlerConfig
    // {
    //     $config  = CrawlerConfig::create($data);
    //     $lexicon = Lexicon::findOrFail($data['lexicon_id']);

    //     $this->crawlerTaskService
    //         ->createTaskFromConfig($config, $lexicon);

    //     return $config;
    // }

    public function updateConfig(CrawlerConfig $config, array $data): bool
    {
        return $config->update($data);
    }

    public function deleteConfig(CrawlerConfig $config): ?bool
    {
        return $config->delete();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return CrawlerConfig::with('lexicon')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function createConfig(array $data): CrawlerConfig
    {

        $domains = collect($data['sources'])
            ->map(function ($url) {
                $url = trim($url);

                if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
                    $url = 'https://'.$url;
                }

                $host = parse_url($url, PHP_URL_HOST);

                if (! $host) {
                    return null;
                }

                return 'https://'.preg_replace('/^www\./', '', strtolower($host));
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();
        $data['sources'] = $domains;

        $config = CrawlerConfig::create($data);
        $this->globalWhitelistService->createMany($domains);
        $lexicon = Lexicon::findOrFail($data['lexicon_id']);

        $this->crawlerTaskService->createFromConfig($config, $lexicon);

        return $config;
    }

    public function update(CrawlerConfig $config, array $data): bool
    {
        return $config->update($data);
    }

    public function delete(CrawlerConfig $config): ?bool
    {
        return $config->delete();
    }

    private function extractDomain(string $url): ?string
    {
        $url = trim($url);
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://'.$url;
        }
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return null;
        }

        return preg_replace('/^www\./', '', strtolower($host));
    }
}
