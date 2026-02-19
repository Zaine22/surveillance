<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LexiconKeyword\StoreLexiconKeywordRequest;
use App\Http\Requests\LexiconKeyword\UpdateLexiconKeywordRequest;
use App\Http\Resources\LexiconKeywordResource;
use App\Services\LexiconKeywordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $file = request()->file('file');
        $this->lexiconKeywordService->import($file);

        return response()->json([
            'message' => 'Lexicon keywords imported successfully',
        ]);
    }

    public function export(string $lexiconId): StreamedResponse
    {
        return $this->lexiconKeywordService->export($lexiconId);
    }
}
