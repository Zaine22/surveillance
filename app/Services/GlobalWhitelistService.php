<?php
namespace App\Services;

use App\Models\GlobalWhitelist;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GlobalWhitelistService
{
    public function getAllGlobalWhiteLists(): Collection
    {
        return GlobalWhitelist::orderBy('created_at', 'desc')->get();
    }

    // public function createMany(array $urls): Collection
    // {
    //     return DB::transaction(function () use ($urls) {

    //         $normalizedUrls = collect($urls)
    //             ->map(fn ($url) => rtrim(trim($url), '/'))
    //             ->unique()
    //             ->values();

    //         $existingUrls = GlobalWhitelist::whereIn('url', $normalizedUrls)
    //             ->pluck('url')
    //             ->all();

    //         $insertData = $normalizedUrls
    //             ->diff($existingUrls)
    //             ->map(fn ($url) => [
    //                 'id' => (string) Str::uuid(),
    //                 'url' => $url,
    //                 'created_at' => now(),
    //                 'updated_at' => now(),
    //             ])
    //             ->values()
    //             ->toArray();

    //         if (! empty($insertData)) {
    //             GlobalWhitelist::insert($insertData);
    //         }

    //         return GlobalWhitelist::whereIn(
    //             'url',
    //             collect($insertData)->pluck('url')
    //         )->get();
    //     });
    // }

    public function createMany(array $urls): Collection
    {
        return DB::transaction(function () use ($urls) {

            // Normalize URLs to extract only the domain
            $normalizedUrls = collect($urls)
                ->map(function ($url) {
                    return $this->extractDomain($url);
                })
                ->filter() // Remove null values
                ->unique()
                ->values();

            $existingUrls = GlobalWhitelist::whereIn('url', $normalizedUrls)
                ->pluck('url')
                ->all();

            $insertData = $normalizedUrls
                ->diff($existingUrls)
                ->map(fn($url) => [
                    'id'         => (string) Str::uuid(),
                    'url'        => $url,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->toArray();

            if (! empty($insertData)) {
                GlobalWhitelist::insert($insertData);
            }

            // Return all matching URLs (existing + new)
            return GlobalWhitelist::whereIn('url', $normalizedUrls)->get();
        });
    }

    /**
     * Extract domain from URL
     * Example: https://www.youtube.com/ -> youtube.com
     */
    private function extractDomain(string $url): ?string
    {
        // Add scheme if not present
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'http://' . $url;
        }

        $parsedUrl = parse_url($url);

        if (!isset($parsedUrl['host'])) {
            return null;
        }

        $host = strtolower($parsedUrl['host']);

        // Remove 'www.' prefix if present
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
    }

    public function getGlobalWhitelistById(string $id): ?GlobalWhitelist
    {
        return GlobalWhitelist::find($id);
    }

    public function delete(GlobalWhitelist $whitelist): ?bool
    {
        return $whitelist->delete();
    }
}