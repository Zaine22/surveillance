<?php

namespace App\Services;

use App\Models\LexiconKeyword;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

    public function import(UploadedFile $file): void
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {

                // Skip header row
                if ($index === 1) {
                    continue;
                }

                if (empty($row['A']) || empty($row['B'])) {
                    continue;
                }

                LexiconKeyword::create([
                    'id' => Str::uuid(),
                    'lexicon_id' => trim($row['A']),
                    'keywords' => $this->parseKeywords($row['B']),
                    'crawl_hit_count' => (int) ($row['C'] ?? 0),
                    'case_count' => (int) ($row['D'] ?? 0),
                    'status' => $row['E'] ?? 'enabled',
                ]);
            }
        });
    }

    private function parseKeywords(string $keywords): array
    {
        return array_values(array_unique(array_filter(
            array_map(
                fn ($word) => trim($word),
                preg_split('/[,|\n]/', $keywords)
            )
        )));
    }
}
