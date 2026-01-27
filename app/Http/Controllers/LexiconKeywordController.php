<?php

namespace App\Http\Controllers;

use App\Http\Requests\LexiconKeyword\StoreLexiconKeywordRequest;
use App\Http\Requests\LexiconKeyword\UpdateLexiconKeywordRequest;
use App\Http\Resources\LexiconKeywordResource;
use App\Models\LexiconKeyword;
use App\Services\LexiconKeywordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LexiconKeywordController extends Controller
{
    public function __construct(protected LexiconKeywordService $lexiconKeywordService) {}

    public function index(string $lexiconId): AnonymousResourceCollection
    {
        $perPage = request()->integer('per_page', 20);

        $keywords = $this->lexiconKeywordService
            ->getKeywordsByLexicon($lexiconId, $perPage);

        return LexiconKeywordResource::collection($keywords);
    }

    public function show(string $id): LexiconKeywordResource|JsonResponse
    {
        $keyword = $this->lexiconKeywordService
            ->getLexiconKeywordById($id);

        if (! $keyword) {
            return response()->json(['message' => 'Lexicon keyword not found'], 404);
        }

        return new LexiconKeywordResource($keyword);
    }

    public function store(StoreLexiconKeywordRequest $request): LexiconKeywordResource
    {
        $lexiconKeyword = $this->lexiconKeywordService
            ->createLexiconKeyword($request->validated());

        return new LexiconKeywordResource($lexiconKeyword);
    }

    public function update(UpdateLexiconKeywordRequest $request, string $id): LexiconKeywordResource|JsonResponse
    {
        $keyword = $this->lexiconKeywordService
            ->getLexiconKeywordById($id);

        if (! $keyword) {
            return response()->json(['message' => 'Lexicon keyword not found'], 404);
        }

        $this->lexiconKeywordService
            ->updateLexiconKeyword($keyword, $request->validated());

        return new LexiconKeywordResource($keyword);
    }

    public function destroy(string $id): JsonResponse
    {
        $keyword = $this->lexiconKeywordService
            ->getLexiconKeywordById($id);

        if (! $keyword) {
            return response()->json(['message' => 'Lexicon keyword not found'], 404);
        }

        $this->lexiconKeywordService->deleteLexiconKeyword($keyword);

        return response()->json(
            ['message' => 'Lexicon keyword deleted successfully'],
            200
        );
    }

    public function import()
    {
        request()->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $spreadsheet = IOFactory::load(request()->file('file')->getRealPath());
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

        return response()->json([
            'message' => 'Lexicon keywords imported successfully',
        ]);
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
