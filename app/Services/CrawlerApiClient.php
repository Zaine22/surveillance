<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Exceptions\CrawlerApiException;

class CrawlerApiClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.crawler_api.url');
        $this->apiKey = config('services.crawler_api.key');
        $this->timeout = config('services.crawler_api.timeout', 30);
    }

    /**
     * Dispatch a crawler task
     */
    public function dispatchTask(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/crawler/dispatch", $data);

            if ($response->failed()) {
                throw new CrawlerApiException(
                    "Failed to dispatch task: " . $response->body(),
                    $response->status()
                );
            }

            return $response->json();

        } catch (\Throwable $e) {
            Log::error('Crawler API dispatch failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw new CrawlerApiException("Crawler API error: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get crawler results
     */
    public function getResults(int $limit = 10, int $timeout = 5000): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/crawler/results", [
                    'limit' => $limit,
                    'timeout' => $timeout,
                ]);

            if ($response->failed()) {
                throw new CrawlerApiException(
                    "Failed to get results: " . $response->body(),
                    $response->status()
                );
            }

            return $response->json();

        } catch (\Throwable $e) {
            Log::error('Crawler API get results failed', [
                'error' => $e->getMessage(),
            ]);
            throw new CrawlerApiException("Crawler API error: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Acknowledge processed messages
     */
    public function acknowledgeResults(array $messageIds): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/crawler/acknowledge", [
                    'message_ids' => $messageIds,
                ]);

            if ($response->failed()) {
                throw new CrawlerApiException(
                    "Failed to acknowledge results: " . $response->body(),
                    $response->status()
                );
            }

            return $response->json();

        } catch (\Throwable $e) {
            Log::error('Crawler API acknowledge failed', [
                'error' => $e->getMessage(),
                'message_ids' => $messageIds,
            ]);
            throw new CrawlerApiException("Crawler API error: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check API health
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}/health");

            if ($response->failed()) {
                throw new CrawlerApiException(
                    "Health check failed: " . $response->body(),
                    $response->status()
                );
            }

            return $response->json();

        } catch (\Throwable $e) {
            Log::error('Crawler API health check failed', [
                'error' => $e->getMessage(),
            ]);
            throw new CrawlerApiException("Crawler API error: " . $e->getMessage(), 0, $e);
        }
    }
}
