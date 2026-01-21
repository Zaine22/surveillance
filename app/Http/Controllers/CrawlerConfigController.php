<?php

namespace App\Http\Controllers;

use App\Http\Requests\CrawlerConfig\StoreCrawlerConfigRequest;
use App\Http\Requests\CrawlerConfig\UpdateCrawlerConfigRequest;
use App\Http\Resources\CrawlerConfigResource;
use App\Services\CrawlerConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CrawlerConfigController extends Controller
{
    public function __construct(
        protected CrawlerConfigService $crawlerConfigService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $configs = $this->crawlerConfigService->getAllConfigs();

        return CrawlerConfigResource::collection($configs);
    }

    public function store(StoreCrawlerConfigRequest $request): CrawlerConfigResource
    {
        $config = $this->crawlerConfigService->createConfig($request->validated());

        return new CrawlerConfigResource($config->load('lexicon'));
    }

    public function show(string $id): CrawlerConfigResource|JsonResponse
    {
        $config = $this->crawlerConfigService->getConfigById($id);

        if (! $config) {
            return response()->json(['message' => 'Crawler config not found'], 404);
        }

        return new CrawlerConfigResource($config);
    }

    public function update(
        UpdateCrawlerConfigRequest $request,
        string $id
    ): CrawlerConfigResource|JsonResponse {
        $config = $this->crawlerConfigService->getConfigById($id);

        if (! $config) {
            return response()->json(['message' => 'Crawler config not found'], 404);
        }

        $this->crawlerConfigService->updateConfig($config, $request->validated());

        return new CrawlerConfigResource($config->refresh());
    }

    public function destroy(string $id): JsonResponse
    {
        $config = $this->crawlerConfigService->getConfigById($id);

        if (! $config) {
            return response()->json(['message' => 'Crawler config not found'], 404);
        }

        $this->crawlerConfigService->deleteConfig($config);

        return response()->json(['message' => 'Crawler config deleted successfully'], 200);
    }
}
