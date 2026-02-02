<?php

namespace App\Services;

use App\Models\Lexicon;
use App\Models\LexiconKeyword;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LexiconService
{
    public function getAllLexicons(int $perPage = 15): LengthAwarePaginator
    {
        return Lexicon::with('keywords')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getLexiconById(string $id): ?Lexicon
    {
        return Lexicon::with('keywords')->find($id);
    }

    public function createLexicon(array $data): Lexicon
    {
        return DB::transaction(function () use ($data) {

            // 1. Extract keywords payload
            $keywordGroups = $data['keywords'] ?? [];
            unset($data['keywords']);

            // 2. Create lexicon
            $lexicon = Lexicon::create($data);

            // 3. Insert keyword groups
            foreach ($keywordGroups as $group) {

                // safety check (must be array)
                if (! is_array($group) || empty($group)) {
                    continue;
                }

                LexiconKeyword::create([
                    'lexicon_id' => $lexicon->id,
                    'keywords' => array_values(array_unique($group)),
                    'crawl_hit_count' => 0,
                    'case_count' => 0,
                    'status' => 'enabled',
                ]);
            }

            return $lexicon->load('keywords');
        });
    }

    public function updateLexicon(Lexicon $lexicon, array $data): bool
    {
        return $lexicon->update($data);

    }

    public function deleteLexicon(Lexicon $lexicon): ?bool
    {
        return $lexicon->delete();
    }
}
