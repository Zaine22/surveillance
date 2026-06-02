<?php
namespace App\Services;

use App\Models\Lexicon;
use App\Models\LexiconKeyword;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        return LexiconKeyword::with('translations')->find($id);
    }

    public function createLexiconKeyword(array $data): array
    {
        $keywords        = $data['keywords'];
        $createdKeywords = [];

        DB::transaction(function () use ($data, $keywords, &$createdKeywords) {
            // Get existing keywords for this lexicon to check for duplicates
            $existingKeywords = LexiconKeyword::where('lexicon_id', $data['lexicon_id'])
                ->get()
                ->pluck('keywords')
                ->flatten()
                ->map(fn($k) => strtolower(trim($k)))
                ->toArray();

            foreach ($keywords as $keywordItem) {
                $keywordArray = (array) $keywordItem;

                // Normalize and deduplicate keywords
                $normalizedKeywords = array_map(fn($k) => trim($k), $keywordArray);
                $uniqueKeywords = array_values(array_unique($normalizedKeywords, SORT_STRING));

                // Filter out keywords that already exist in the lexicon
                $filteredKeywords = array_filter($uniqueKeywords, function($keyword) use (&$existingKeywords) {
                    $lowerKeyword = strtolower($keyword);
                    if (in_array($lowerKeyword, $existingKeywords)) {
                        return false;
                    }
                    $existingKeywords[] = $lowerKeyword;
                    return true;
                });

                // Only create if there are keywords remaining after filtering
                if (!empty($filteredKeywords)) {
                    $createdKeywords[] = LexiconKeyword::create([
                        'lexicon_id'      => $data['lexicon_id'],
                        'keywords'        => array_values($filteredKeywords),
                        'status'          => $data['status'] ?? 'enabled',
                        'crawl_hit_count' => $data['crawl_hit_count'] ?? 0,
                        'case_count'      => $data['case_count'] ?? 0,
                    ]);
                }
            }
        });

        return $createdKeywords;
    }

    public function updateLexiconKeyword(
        LexiconKeyword $lexiconKeyword,
        array $data
    ): bool {
        DB::beginTransaction();

        try {
            $updateData = [];

            // Handle translations if provided
            if (isset($data['translations']) && is_array($data['translations'])) {
                // Update zh (parent keyword's keywords field)
                if (isset($data['translations']['zh']) && is_array($data['translations']['zh'])) {
                    $updateData['keywords'] = $data['translations']['zh'];
                }

                // Update en, ja, and cn as translation records
                foreach (['en', 'ja', 'cn'] as $language) {
                    if (isset($data['translations'][$language]) && is_array($data['translations'][$language])) {
                        $this->upsertTranslation(
                            $lexiconKeyword->id,
                            $language,
                            $data['translations'][$language]
                        );
                    }
                }
            }

            // Update the main keyword record
            $result = !empty($updateData) ? $lexiconKeyword->update($updateData) : true;

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteLexiconKeyword(LexiconKeyword $lexiconKeyword): ?bool
    {
        return $lexiconKeyword->delete();
    }

    public function import(UploadedFile $file): void
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true);

        DB::transaction(function () use ($rows) {
            // Track existing keywords per lexicon to prevent duplicates during import
            $existingKeywordsByLexicon = [];

            foreach ($rows as $index => $row) {

                // Skip header row
                if ($index === 1) {
                    continue;
                }

                if (empty($row['A']) || empty($row['B'])) {
                    continue;
                }

                $lexiconId = trim($row['A']);
                $keywords = $this->parseKeywords($row['B']);

                // Initialize existing keywords for this lexicon if not already loaded
                if (!isset($existingKeywordsByLexicon[$lexiconId])) {
                    $existingKeywordsByLexicon[$lexiconId] = LexiconKeyword::where('lexicon_id', $lexiconId)
                        ->get()
                        ->pluck('keywords')
                        ->flatten()
                        ->map(fn($k) => strtolower(trim($k)))
                        ->toArray();
                }

                // Filter out keywords that already exist in the lexicon
                $filteredKeywords = array_filter($keywords, function($keyword) use (&$existingKeywordsByLexicon, $lexiconId) {
                    $lowerKeyword = strtolower($keyword);
                    if (in_array($lowerKeyword, $existingKeywordsByLexicon[$lexiconId])) {
                        return false;
                    }
                    // Add to tracking array to prevent duplicates within the same import
                    $existingKeywordsByLexicon[$lexiconId][] = $lowerKeyword;
                    return true;
                });

                // Only create if there are keywords remaining after filtering
                if (!empty($filteredKeywords)) {
                    LexiconKeyword::create([
                        'id'              => Str::uuid(),
                        'lexicon_id'      => $lexiconId,
                        'keywords'        => array_values($filteredKeywords),
                        'crawl_hit_count' => (int) ($row['C'] ?? 0),
                        'case_count'      => (int) ($row['D'] ?? 0),
                        'status'          => $row['E'] ?? 'enabled',
                    ]);
                }
            }
        });
    }

    private function parseKeywords(string $keywords): array
    {
        return array_values(array_unique(array_filter(
            array_map(
                fn($word) => trim($word),
                preg_split('/[,|\n]/', $keywords)
            )
        )));
    }

    public function export(string $lexiconId): StreamedResponse
    {
        $lexicon = Lexicon::findOrFail($lexiconId);
        $data    = LexiconKeyword::where('lexicon_id', $lexiconId)->get();

        $spreadsheet = new Spreadsheet;
        $sheet       = $spreadsheet->getActiveSheet();

        // Header row
        // $sheet->setCellValue('A1', '詞庫ID');
        $sheet->setCellValue('A1', '關鍵字(支援多組)');
        $sheet->setCellValue('B1', '爬網命中數');
        $sheet->setCellValue('C1', '案件總數');
        $sheet->setCellValue('D1', '狀態');

        $rowNumber = 2;

        foreach ($data as $item) {
            // $sheet->setCellValue('A'.$rowNumber, $item->lexicon_id);
            $sheet->setCellValue(
                'A' . $rowNumber,
                is_array($item->keywords)
                    ? implode(',', $item->keywords)
                    : $item->keywords
            );
            $sheet->setCellValue('B' . $rowNumber, $item->crawl_hit_count);
            $sheet->setCellValue('C' . $rowNumber, $item->case_count);
            $sheet->setCellValue('D' . $rowNumber, $item->status === 'enabled' ? '上架' : '下架');

            $rowNumber++;
        }

        $writer = new Xlsx($spreadsheet);

        // $fileName = 'lexicon_keywords_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        $lexiconName = preg_replace('/[\\\\\/:*?"<>|]/', '_', $lexicon->name);

        $fileName = $lexiconName . '_關鍵字列表.xlsx';

        return response()->streamDownload(
            function () use ($writer) {
                $writer->save('php://output');
            },
            $fileName,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    public function upsertTranslation(
        string $parentId,
        string $language,
        array $keywords
    ): ?LexiconKeyword {
        $parent = LexiconKeyword::whereNull('parent_id')->find($parentId);

        if (! $parent) {
            return null;
        }

        // Get ALL existing keywords in this lexicon (no exclusions - check against everything)
        $existingKeywords = LexiconKeyword::where('lexicon_id', $parent->lexicon_id)
            ->get()
            ->pluck('keywords')
            ->flatten()
            ->map(fn($k) => strtolower(trim($k)))
            ->toArray();

        // Normalize and deduplicate keywords
        $normalizedKeywords = array_map(fn($k) => trim($k), $keywords);
        $uniqueKeywords = array_values(array_unique($normalizedKeywords, SORT_STRING));

        // Filter out keywords that already exist ANYWHERE in the lexicon
        $filteredKeywords = array_filter($uniqueKeywords, function($keyword) use ($existingKeywords) {
            $lowerKeyword = strtolower($keyword);
            // Reject if exists anywhere in the lexicon
            return !in_array($lowerKeyword, $existingKeywords);
        });

        // If no keywords remain after filtering, return null or handle appropriately
        if (empty($filteredKeywords)) {
            return null;
        }

        return LexiconKeyword::updateOrCreate(
            [
                'parent_id' => $parent->id,
                'language'  => $language,
            ],
            [
                'lexicon_id'      => $parent->lexicon_id,
                'keywords'        => array_values($filteredKeywords),
                'crawl_hit_count' => 0,
                'case_count'      => 0,
                'status'          => 'enabled',
            ]
        );
    }

    public function getTranslations(string $parentId): ?LexiconKeyword
    {
        return LexiconKeyword::with('translations')
            ->whereNull('parent_id')
            ->find($parentId);
    }
}
