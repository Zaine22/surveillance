<?php
namespace App\Services;

use App\Models\LexiconKeyword;

class KeywordRankingService
{
    private function parseKeywords($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (empty($raw)) {
            return [];
        }

        $raw = (string) $raw;

        $fixed = str_replace('""', '"', $raw);

        $decoded = json_decode($fixed, true);

        if (! is_array($decoded)) {
            $clean = trim($fixed, '"');
            return $clean !== '' ? [$clean] : [];
        }

        return $decoded;
    }

    public function processHit($input): void
    {
        $inputKeywords = $this->parseKeywords($input);

        if (empty($inputKeywords)) {
            return;
        }

        $inputKeywords = array_unique(array_filter(array_map(function ($k) {
            return is_string($k) ? strtolower(trim($k)) : null;
        }, $inputKeywords)));

        if (empty($inputKeywords)) {
            return;
        }

        $inputMap = array_flip($inputKeywords);

        $rows = LexiconKeyword::where('status', 'enabled')->get();

        $processedGroups = [];

        foreach ($rows as $row) {

            $rawKeywords = $row->keywords;

            if (is_array($rawKeywords)) {
                $hash = md5(json_encode($rawKeywords));
            } elseif (is_string($rawKeywords)) {
                $hash = md5($rawKeywords);
            } else {
                $hash = md5((string) $rawKeywords);
            }

            if (isset($processedGroups[$hash])) {
                continue;
            }

            $processedGroups[$hash] = true;

            $lexiconKeywords = $this->parseKeywords($rawKeywords);

            if (empty($lexiconKeywords)) {
                continue;
            }

            foreach ($lexiconKeywords as $keyword) {

                if (! is_string($keyword)) {
                    continue;
                }

                $keyword = strtolower(trim($keyword));

                if ($keyword === '') {
                    continue;
                }

                if (isset($inputMap[$keyword])) {
                    $row->increment('crawl_hit_count');
                    break;
                }
            }
        }
    }

    public function getRankingWithDate($from, $to, int $limit = 5): array
    {
        $rows = LexiconKeyword::with('lexicon')
            ->where('status', 'enabled')
            ->whereBetween('updated_at', [$from, $to])
            ->get();

        $keywordStats = [];

        foreach ($rows as $row) {

            $keywords = $this->parseKeywords($row->keywords);

            if (empty($keywords)) {
                continue;
            }

            $hitCount = (int) ($row->crawl_hit_count ?? 0);

            if ($hitCount <= 0) {
                continue;
            }

            $uniqueKeywords = [];

            foreach ($keywords as $keyword) {

                if (! is_string($keyword)) {
                    continue;
                }

                $keyword = strtolower(trim($keyword));

                if ($keyword === '') {
                    continue;
                }

                $uniqueKeywords[$keyword] = true;
            }

            foreach (array_keys($uniqueKeywords) as $keyword) {

                $key = $keyword . '_' . $row->lexicon_id;

                if (! isset($keywordStats[$key])) {
                    $keywordStats[$key] = [
                        'keyword'      => $keyword,
                        'lexicon_id'   => $row->lexicon_id,
                        'lexicon_name' => $row->lexicon->name ?? null,
                        'hit_count'    => 0,
                    ];
                }

                $keywordStats[$key]['hit_count'] += $hitCount;
            }
        }

        usort($keywordStats, fn($a, $b) => $b['hit_count'] <=> $a['hit_count']);

        return array_slice(array_values($keywordStats), 0, $limit);
    }

}
