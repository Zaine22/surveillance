<?php
namespace App\Services;

use App\Jobs\CrawlerScheduledJob;
use App\Models\CrawlerConfig;
use App\Models\Lexicon;
use App\Services\CrawlerTaskService;
use App\Services\GlobalWhitelistService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CrawlerConfigService extends BaseFilterService
{
    public function __construct(
        protected CrawlerTaskService $crawlerTaskService,
        protected GlobalWhitelistService $globalWhitelistService
    ) {}

    public function getAllConfigs(array $filters): LengthAwarePaginator
    {
        $query = CrawlerConfig::with('lexicon', 'tasks');
        if (! empty($filters['search'])) {

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
        if (! empty($filters['status'])) {

            $status = $filters['status'];

            // $query->whereHas('tasks', function ($q) use ($status) {
            //     $q->where('status', $status);
            // });
            $query->where('status', $status);
        }

        return $this->applyFilters(
            $query,
            $filters,
            [],
            false,
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
        // Check if critical fields have changed that require job rescheduling
        $shouldRescheduleJobs = false;

        if (isset($data['sources']) && $data['sources'] != $config->sources) {
            $shouldRescheduleJobs = true;
        }

        if (isset($data['frequency_code']) && $data['frequency_code'] != $config->frequency_code) {
            $shouldRescheduleJobs = true;
        }

        if (isset($data['from']) && $data['from'] != $config->from) {
            $shouldRescheduleJobs = true;
        }

        if (isset($data['to']) && $data['to'] != $config->to) {
            $shouldRescheduleJobs = true;
        }

        // Delete existing scheduled jobs if critical fields changed
        if ($shouldRescheduleJobs) {
            $this->deleteScheduledJobs($config->id);
        }

        // Process sources if provided
        if (isset($data['sources'])) {
            $domains = collect($data['sources'])
                ->map(function ($url) {

                    $url = trim($url);

                    if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
                        $url = 'https://' . $url;
                    }

                    if (! filter_var($url, FILTER_VALIDATE_URL)) {
                        return null;
                    }

                    $parts = parse_url($url);

                    if (empty($parts['host'])) {
                        return null;
                    }

                    $scheme = $parts['scheme'] ?? 'https';
                    $host   = strtolower($parts['host']);
                    $path   = $parts['path'] ?? '';
                    $query  = isset($parts['query']) ? '?' . $parts['query'] : '';

                    return "{$scheme}://{$host}{$path}{$query}";
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $data['sources'] = $domains;
        }

        // Handle status logic based on from/to dates
        if (isset($data['from'])) {
            $from = ! empty($data['from']) ? Carbon::parse($data['from']) : now();
            $to   = isset($data['to']) && ! empty($data['to']) ? Carbon::parse($data['to']) : null;

            if ($from->startOfDay()->gt(now()->startOfDay())) {
                $data['status'] = 'pending';
            } elseif (! isset($data['status'])) {
                $data['status'] = 'enabled';
            }

            // Handle scheduled job if status is pending
            if ($data['status'] === 'pending' && $to && isset($data['frequency_code'])) {
                CrawlerScheduledJob::dispatch($config->id, $data['frequency_code'], $to)
                    ->delay($from);
            }

            // Create task if status is enabled and lexicon_id is provided or exists
            if ($data['status'] === 'enabled') {
                $lexiconId = $data['lexicon_id'] ?? $config->lexicon_id;
                if ($lexiconId) {
                    $lexicon = Lexicon::findOrFail($lexiconId);
                    $this->crawlerTaskService->createFromConfig($config, $lexicon);
                }
            }
        }

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

    //this is correct createConfig
    // public function createConfig(array $data): CrawlerConfig
    // {
    //     $domains = collect($data['sources'])
    //         ->map(function ($url) {

    //             $url = trim($url);

    //             if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
    //                 $url = 'https://' . $url;
    //             }

    //             if (! filter_var($url, FILTER_VALIDATE_URL)) {
    //                 return null;
    //             }

    //             $parts = parse_url($url);

    //             if (empty($parts['host'])) {
    //                 return null;
    //             }

    //             $scheme = $parts['scheme'] ?? 'https';
    //             $host   = strtolower($parts['host']);
    //             $path   = $parts['path'] ?? '';
    //             $query  = isset($parts['query']) ? '?' . $parts['query'] : '';

    //             return "{$scheme}://{$host}{$path}{$query}";
    //         })
    //         ->filter()
    //         ->unique()
    //         ->values()
    //         ->toArray();

    //     $data['sources'] = $domains;

    //     $config = CrawlerConfig::create($data);

    //     $lexicon = Lexicon::findOrFail($data['lexicon_id']);

    //     $this->crawlerTaskService->createFromConfig($config, $lexicon);

    //     if ($data['status'] === 'enabled' && ! empty($data['from']) && ! empty($data['to'])) {
    //         $from = Carbon::parse($data['from']);
    //         $to   = Carbon::parse($data['to']);

    //         CrawlerScheduledJob::dispatch($config->id, $data['frequency_code'], $to)
    //             ->delay($from);
    //     }

    //     return $config;
    // }

    public function createConfig(array $data): CrawlerConfig
    {
        $domains = collect($data['sources'])
            ->map(function ($url) {

                $url = trim($url);

                if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
                    $url = 'https://' . $url;
                }

                if (! filter_var($url, FILTER_VALIDATE_URL)) {
                    return null;
                }

                $parts = parse_url($url);

                if (empty($parts['host'])) {
                    return null;
                }

                $scheme = $parts['scheme'] ?? 'https';
                $host   = strtolower($parts['host']);
                $path   = $parts['path'] ?? '';
                $query  = isset($parts['query']) ? '?' . $parts['query'] : '';

                return "{$scheme}://{$host}{$path}{$query}";
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $data['sources'] = $domains;

        $from = ! empty($data['from']) ? Carbon::parse($data['from']) : now();
        $to   = ! empty($data['to']) ? Carbon::parse($data['to']) : null;

        if ($from->startOfDay()->gt(now()->startOfDay())) {
            $data['status'] = 'pending';
        } else {
            $data['status'] = 'enabled';
        }

        $config = CrawlerConfig::create($data);

        $lexicon = Lexicon::findOrFail($data['lexicon_id']);

        if ($data['status'] === 'enabled') {
            $this->crawlerTaskService->createFromConfig($config, $lexicon);
        }

        if ($data['status'] === 'pending' && $to && isset($data['frequency_code'])) {
            CrawlerScheduledJob::dispatch($config->id, $data['frequency_code'], $to)
                ->delay($from);
        }

        return $config;
    }

    // public function update(CrawlerConfig $config, array $data): bool
    // {
    //     // Process sources if provided
    //     if (isset($data['sources'])) {
    //         $domains = collect($data['sources'])
    //             ->map(function ($url) {

    //                 $url = trim($url);

    //                 if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
    //                     $url = 'https://' . $url;
    //                 }

    //                 if (! filter_var($url, FILTER_VALIDATE_URL)) {
    //                     return null;
    //                 }

    //                 $parts = parse_url($url);

    //                 if (empty($parts['host'])) {
    //                     return null;
    //                 }

    //                 $scheme = $parts['scheme'] ?? 'https';
    //                 $host   = strtolower($parts['host']);
    //                 $path   = $parts['path'] ?? '';
    //                 $query  = isset($parts['query']) ? '?' . $parts['query'] : '';

    //                 return "{$scheme}://{$host}{$path}{$query}";
    //             })
    //             ->filter()
    //             ->unique()
    //             ->values()
    //             ->toArray();

    //         $data['sources'] = $domains;

    //     }

    //     // Handle status logic based on from/to dates
    //     if (isset($data['from'])) {
    //         $from = ! empty($data['from']) ? Carbon::parse($data['from']) : now();
    //         $to   = isset($data['to']) && ! empty($data['to']) ? Carbon::parse($data['to']) : null;

    //         if ($from->startOfDay()->gt(now()->startOfDay())) {
    //             $data['status'] = 'pending';
    //         } elseif (! isset($data['status'])) {
    //             $data['status'] = 'enabled';
    //         }

    //         // Handle scheduled job if status is pending
    //         if ($data['status'] === 'pending' && $to && isset($data['frequency_code'])) {
    //             CrawlerScheduledJob::dispatch($config->id, $data['frequency_code'], $to)
    //                 ->delay($from);
    //         }

    //         // Create task if status is enabled and lexicon_id is provided or exists
    //         if ($data['status'] === 'enabled') {
    //             $lexiconId = $data['lexicon_id'] ?? $config->lexicon_id;
    //             if ($lexiconId) {
    //                 $lexicon = Lexicon::findOrFail($lexiconId);
    //                 $this->crawlerTaskService->createFromConfig($config, $lexicon);
    //             }
    //         }
    //     }
    // }

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

    /**
     * Delete all scheduled jobs for a specific crawler config
     */
    private function deleteScheduledJobs(string $configId): void
    {
        DB::table('jobs')->where('payload', 'like', '%"configId":"' . $configId . '"%')->delete();
    }
}
