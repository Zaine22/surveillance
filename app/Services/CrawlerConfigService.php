<?php

namespace App\Services;

use App\Models\CrawlerConfig;
use Illuminate\Pagination\LengthAwarePaginator;

class CrawlerConfigService
{
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

    public function createConfig(array $data): CrawlerConfig
    {
        return CrawlerConfig::create($data);
    }

    public function updateConfig(CrawlerConfig $config, array $data): bool
    {
        return $config->update($data);
    }

    public function deleteConfig(CrawlerConfig $config): ?bool
    {
        return $config->delete();
    }
}
