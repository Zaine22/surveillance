<?php

namespace App\Services;

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

    public function export(string $lexiconId): StreamedResponse
    {
        $data = LexiconKeyword::where('lexicon_id', $lexiconId)->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Header row
        $sheet->setCellValue('A1', 'Lexicon ID');
        $sheet->setCellValue('B1', 'Keywords');
        $sheet->setCellValue('C1', 'Crawl Hit Count');
        $sheet->setCellValue('D1', 'Case Count');
        $sheet->setCellValue('E1', 'Status');

        $rowNumber = 2;

        foreach ($data as $item) {
            $sheet->setCellValue('A'.$rowNumber, $item->lexicon_id);
            $sheet->setCellValue(
                'B'.$rowNumber,
                is_array($item->keywords)
                    ? implode(',', $item->keywords)
                    : $item->keywords
            );
            $sheet->setCellValue('C'.$rowNumber, $item->crawl_hit_count);
            $sheet->setCellValue('D'.$rowNumber, $item->case_count);
            $sheet->setCellValue('E'.$rowNumber, $item->status);

            $rowNumber++;
        }

        $writer = new Xlsx($spreadsheet);

        // ✅ Timestamp filename
        $fileName = 'lexicon_keywords_'.now()->format('Y-m-d_H-i-s').'.xlsx';

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
}
