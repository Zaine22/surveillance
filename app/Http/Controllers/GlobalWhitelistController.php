<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalWhitelist\StoreGlobalWhitelistRequest;
use App\Http\Resources\GlobalWhitelistResource;
use App\Services\GlobalWhitelistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GlobalWhitelistController extends Controller
{
    public function __construct(
        protected GlobalWhitelistService $globalWhitelistService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return GlobalWhitelistResource::collection(
            $this->globalWhitelistService->getAllGlobalWhiteLists()
        );
    }

    public function store(StoreGlobalWhitelistRequest $request): AnonymousResourceCollection
    {
        $items = $this->globalWhitelistService
            ->createMany($request->validated()['url']);

        return GlobalWhitelistResource::collection($items);
    }

    public function destroy(string $id): JsonResponse
    {
        $whitelist = $this->globalWhitelistService->getGlobalWhitelistById($id);

        if (! $whitelist) {
            return response()->json(['message' => 'Whitelist not found'], 404);
        }

        $this->globalWhitelistService->delete($whitelist);

        return response()->json(['message' => 'Whitelist deleted successfully'], 200);
    }
}
