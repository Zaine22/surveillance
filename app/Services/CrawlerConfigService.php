<?php
namespace App\Services;

use App\Models\CrawlerConfig;
use App\Models\Lexicon;
use App\Services\CrawlerTaskService;
use App\Services\GlobalWhitelistService;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Jobs\CrawlerScheduledJob;
use Carbon\Carbon;

class CrawlerConfigService extends BaseFilterService
{
    public function __construct(
        protected CrawlerTaskService $crawlerTaskService,
        protected GlobalWhitelistService $globalWhitelistService
    ) {}

     public function getAllConfigs(array $filters): LengthAwarePaginator
    {
        $query = CrawlerConfig::with('lexicon');
        if (!empty($filters['search'])) {

    $search = strtolower($filters['search']);

    $query->where(function ($q) use ($search) {

        $q->whereRaw(
            "LOWER(name) LIKE ?",
            ["%{$search}%"]
        )
        ->orWhereHas('lexicon', function ($lexicon) use ($search) {
            $lexicon->whereRaw(
                "LOWER(name) LIKE ?",
                ["%{$search}%"]
             );
            });
        });
    }
        return $this->applyFilters(
            $query,
            $filters,
            [],
            true,
        );
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
                    $url = 'https://' . $url;
                }

                $parts = parse_url($url);

                if (! isset($parts['host'])) {
                    return null;
                }

                $scheme = $parts['scheme'] ?? 'https';
                $host   = preg_replace('/^www\./', '', strtolower($parts['host']));
                $path   = $parts['path'] ?? '';
                $query  = isset($parts['query']) ? '?' . $parts['query'] : '';

                return $scheme . '://' . $host . $path . $query;
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

        if ($data['status'] === 'enabled' && ! empty($data['from']) && ! empty($data['to'])) {
            $from = Carbon::parse($data['from']);
            $to = Carbon::parse($data['to']);

            CrawlerScheduledJob::dispatch($config->id, $data['frequency_code'], $to)
                ->delay($from);
        }

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
            $url = 'https://' . $url;
        }
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return null;
        }

        return preg_replace('/^www\./', '', strtolower($host));
    }
}
