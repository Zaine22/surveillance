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

            $normalizedUrls = collect($urls)
                ->map(fn ($url) => strtolower(trim($url)))
                ->map(fn ($url) => rtrim($url, '/'))
                ->unique()
                ->values();

            $existingUrls = GlobalWhitelist::whereIn('url', $normalizedUrls)
                ->pluck('url')
                ->all();

            $insertData = $normalizedUrls
                ->diff($existingUrls)
                ->map(fn ($url) => [
                    'id' => (string) Str::uuid(),
                    'url' => $url,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->toArray();

            if (! empty($insertData)) {
                GlobalWhitelist::insert($insertData);
            }

            return GlobalWhitelist::whereIn(
                'url',
                collect($insertData)->pluck('url')
            )->get();
        });
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
