<?php

namespace App\Services;

use App\Models\Lexicon;
use Illuminate\Pagination\LengthAwarePaginator;

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
        return Lexicon::create($data);
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
