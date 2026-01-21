<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lexicon\StoreLexiconRequest;
use App\Http\Requests\Lexicon\UpdateLexiconRequest;
use App\Http\Resources\LexiconResource;
use App\Services\LexiconService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LexiconController extends Controller
{
    public function __construct(protected LexiconService $lexiconService) {}

    public function index(): AnonymousResourceCollection
    {
        $lexicons = $this->lexiconService->getAllLexicons();

        return LexiconResource::collection($lexicons);
    }

    public function store(StoreLexiconRequest $request): LexiconResource
    {
        $lexicon = $this->lexiconService->createLexicon($request->validated());

        return new LexiconResource($lexicon);
    }

    public function show(string $id): LexiconResource|JsonResponse
    {
        $lexicon = $this->lexiconService->getLexiconById($id);

        if (! $lexicon) {
            return response()->json(['message' => 'Lexicon not found'], 404);
        }

        return new LexiconResource($lexicon);
    }

    public function update(UpdateLexiconRequest $request, string $id): LexiconResource|JsonResponse
    {
        $lexicon = $this->lexiconService->getLexiconById($id);

        if (! $lexicon) {
            return response()->json(['message' => 'Lexicon not found'], 404);
        }

        $this->lexiconService->updateLexicon($lexicon, $request->validated());

        return new LexiconResource($lexicon);
    }

    public function destroy(string $id): JsonResponse
    {
        $lexicon = $this->lexiconService->getLexiconById($id);

        if (! $lexicon) {
            return response()->json(['message' => 'Lexicon not found'], 404);
        }

        $this->lexiconService->deleteLexicon($lexicon);

        return response()->json(['message' => 'Lexicon deleted successfully'], 200);
    }
}
