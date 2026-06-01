<?php

/**
 * Test script for Crawler API Gateway
 *
 * Usage: php test_crawler_api.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configuration
$apiUrl = getenv('CRAWLER_API_URL') ?: 'https://crawler-redis-api.test/api/v1';
$apiKey = getenv('CRAWLER_API_KEY') ?: 'surveillance_key_abc123';

echo "===========================================\n";
echo "Crawler API Gateway Test\n";
echo "===========================================\n";
echo "API URL: $apiUrl\n";
echo "API Key: " . substr($apiKey, 0, 20) . "...\n";
echo "===========================================\n\n";

// Test 1: Health Check (No Auth)
echo "Test 1: Health Check (No Authentication)\n";
echo "-------------------------------------------\n";
try {
    $response = file_get_contents(str_replace('/api/v1', '/health', $apiUrl));
    $data = json_decode($response, true);
    echo "✅ Status: " . ($data['status'] ?? 'unknown') . "\n";
    echo "✅ Redis: " . ($data['redis'] ?? 'unknown') . "\n";
    echo "✅ Time: " . ($data['time'] ?? 'unknown') . "\n";
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Dispatch Task
echo "Test 2: Dispatch Crawler Task\n";
echo "-------------------------------------------\n";
$taskData = [
    'task_item_id' => 'test-' . uniqid(),
    'keywords' => ['test keyword 1', 'test keyword 2'],
    'crawl_location' => 'https://example.com',
    'type' => 'patrol',
];

try {
    $ch = curl_init("$apiUrl/crawler/dispatch");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($taskData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "X-API-Key: $apiKey",
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode === 201 && isset($data['success']) && $data['success']) {
        echo "✅ Task dispatched successfully\n";
        echo "✅ Message ID: " . ($data['message_id'] ?? 'N/A') . "\n";
        echo "✅ Task Item ID: " . $taskData['task_item_id'] . "\n";
    } else {
        echo "❌ Failed to dispatch task\n";
        echo "HTTP Code: $httpCode\n";
        echo "Response: " . print_r($data, true) . "\n";
    }
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Get Results
echo "Test 3: Get Crawler Results\n";
echo "-------------------------------------------\n";
try {
    $ch = curl_init("$apiUrl/crawler/results?limit=5&timeout=1000");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "X-API-Key: $apiKey",
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode === 200 && isset($data['success']) && $data['success']) {
        echo "✅ Results retrieved successfully\n";
        echo "✅ Count: " . ($data['count'] ?? 0) . "\n";
        if (!empty($data['results'])) {
            echo "✅ Sample result:\n";
            $sample = $data['results'][0];
            echo "   - Message ID: " . ($sample['message_id'] ?? 'N/A') . "\n";
            echo "   - Task Item ID: " . ($sample['task_item_id'] ?? 'N/A') . "\n";
            echo "   - Result File: " . ($sample['result_file'] ?? 'N/A') . "\n";
        } else {
            echo "ℹ️  No results available (this is normal if no tasks completed yet)\n";
        }
    } else {
        echo "❌ Failed to get results\n";
        echo "HTTP Code: $httpCode\n";
        echo "Response: " . print_r($data, true) . "\n";
    }
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Acknowledge Results
echo "Test 4: Acknowledge Results\n";
echo "-------------------------------------------\n";
$ackData = [
    'message_ids' => ['test-message-id-1', 'test-message-id-2'],
];

try {
    $ch = curl_init("$apiUrl/crawler/acknowledge");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ackData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "X-API-Key: $apiKey",
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode === 200 && isset($data['success']) && $data['success']) {
        echo "✅ Messages acknowledged successfully\n";
        echo "✅ Acknowledged: " . ($data['acknowledged'] ?? 0) . "\n";
    } else {
        echo "❌ Failed to acknowledge messages\n";
        echo "HTTP Code: $httpCode\n";
        echo "Response: " . print_r($data, true) . "\n";
    }
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Invalid API Key
echo "Test 5: Test Invalid API Key (Should Fail)\n";
echo "-------------------------------------------\n";
try {
    $ch = curl_init("$apiUrl/crawler/results");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: invalid-key-12345',
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode === 403) {
        echo "✅ Correctly rejected invalid API key\n";
        echo "✅ Message: " . ($data['message'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Should have rejected invalid API key\n";
        echo "HTTP Code: $httpCode\n";
    }
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Missing API Key
echo "Test 6: Test Missing API Key (Should Fail)\n";
echo "-------------------------------------------\n";
try {
    $ch = curl_init("$apiUrl/crawler/results");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode === 401) {
        echo "✅ Correctly rejected missing API key\n";
        echo "✅ Message: " . ($data['message'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Should have rejected missing API key\n";
        echo "HTTP Code: $httpCode\n";
    }
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "===========================================\n";
echo "Test Summary\n";
echo "===========================================\n";
echo "All tests completed. Review results above.\n";
echo "\n";
echo "Next Steps:\n";
echo "1. Ensure API Gateway is running\n";
echo "2. Check Redis connection\n";
echo "3. Verify SSL certificate if using HTTPS\n";
echo "4. Update .env with correct API URL and key\n";
echo "===========================================\n";