<?php

namespace App\Services;

use App\Models\LexiconKeyword;
use Illuminate\Pagination\LengthAwarePaginator;

class LexiconKeywordService
{
    public function getKeywordsByLexicon(
        string $lexiconId,
        int $perPage = 20
    ): LengthAwarePaginator {
        return LexiconKeyword::where('lexicon_id', $lexiconId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getLexiconKeywordById(string $id): ?LexiconKeyword
    {
        return LexiconKeyword::find($id);
    }

    public function createLexiconKeyword(array $data): LexiconKeyword
    {
        return LexiconKeyword::create($data);
    }

    public function updateLexiconKeyword(
        LexiconKeyword $lexiconKeyword,
        array $data
    ): bool {
        return $lexiconKeyword->update($data);
    }

    public function deleteLexiconKeyword(LexiconKeyword $lexiconKeyword): ?bool
    {
        return $lexiconKeyword->delete();
    }
}
