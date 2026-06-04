<?php

/**
 * Test script for Redis AI SSL/TLS connection
 *
 * This script tests the secure connection to the AI Redis server
 * using the configured SSL certificate.
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Redis;

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Redis AI SSL/TLS Connection Test ===\n\n";

// Display configuration
echo "Configuration:\n";
echo "  Host: " . env('REDIS_AI_HOST') . "\n";
echo "  Port: " . env('REDIS_AI_PORT') . "\n";
echo "  Scheme: " . env('REDIS_AI_SCHEME', 'tcp') . "\n";
echo "  SSL CA Cert: " . env('REDIS_AI_SSL_CA_CERT') . "\n";
echo "  Verify Peer: " . (env('REDIS_AI_SSL_VERIFY_PEER', true) ? 'true' : 'false') . "\n";
echo "  Verify Peer Name: " . (env('REDIS_AI_SSL_VERIFY_PEER_NAME', true) ? 'true' : 'false') . "\n\n";

// Check if certificate file exists
$certPath = env('REDIS_AI_SSL_CA_CERT');
if (!file_exists($certPath)) {
    echo "❌ ERROR: Certificate file not found at: {$certPath}\n";
    echo "   Please ensure the certificate is placed at the correct location.\n";
    exit(1);
}
echo "✓ Certificate file found\n\n";

try {
    echo "Testing connection...\n";

    // Get Redis connection
    $redis = Redis::connection('ai');

    // Test basic operations
    echo "1. Testing PING command...\n";
    $pong = $redis->ping();
    echo "   Response: {$pong}\n";
    echo "   ✓ PING successful\n\n";

    // Test SET operation
    echo "2. Testing SET command...\n";
    $testKey = 'test:ssl:connection:' . time();
    $testValue = 'SSL connection test successful';
    $redis->set($testKey, $testValue, 'EX', 60); // Expire in 60 seconds
    echo "   ✓ SET successful (key: {$testKey})\n\n";

    // Test GET operation
    echo "3. Testing GET command...\n";
    $retrievedValue = $redis->get($testKey);
    echo "   Retrieved value: {$retrievedValue}\n";

    if ($retrievedValue === $testValue) {
        echo "   ✓ GET successful - value matches\n\n";
    } else {
        echo "   ⚠ GET returned different value\n\n";
    }

    // Test stream operations (used by AI services)
    echo "4. Testing XADD command (stream operation)...\n";
    $streamKey = 'test:stream:' . time();
    $streamId = $redis->xadd($streamKey, '*', [
        'event' => 'test',
        'message' => 'SSL stream test',
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
    echo "   Stream ID: {$streamId}\n";
    echo "   ✓ XADD successful\n\n";

    // Test XREAD
    echo "5. Testing XREAD command...\n";
    $messages = $redis->xread([$streamKey => '0-0'], 1);
    if (!empty($messages[$streamKey])) {
        echo "   ✓ XREAD successful - retrieved " . count($messages[$streamKey]) . " message(s)\n\n";
    } else {
        echo "   ⚠ XREAD returned no messages\n\n";
    }

    // Test hash operations (used by AI task storage)
    echo "6. Testing HSET/HGETALL commands...\n";
    $hashKey = 'test:hash:' . time();
    $redis->hset($hashKey, [
        'status' => 'testing',
        'timestamp' => date('Y-m-d H:i:s'),
        'ssl_enabled' => 'true',
    ]);
    $hashData = $redis->hgetall($hashKey);
    echo "   Retrieved hash data:\n";
    foreach ($hashData as $field => $value) {
        echo "     {$field}: {$value}\n";
    }
    echo "   ✓ Hash operations successful\n\n";

    // Cleanup test keys
    echo "7. Cleaning up test keys...\n";
    $redis->del($testKey, $streamKey, $hashKey);
    echo "   ✓ Cleanup successful\n\n";

    echo "=== ✅ All tests passed! ===\n";
    echo "The Redis AI connection is working correctly with SSL/TLS.\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: Connection failed\n";
    echo "Error message: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Verify the Redis server is configured to accept TLS connections\n";
    echo "2. Check that the certificate file is valid and readable\n";
    echo "3. Ensure the Redis server hostname matches the certificate CN/SAN\n";
    echo "4. Verify firewall rules allow connections to port " . env('REDIS_AI_PORT') . "\n";
    echo "5. Check Redis server logs for connection errors\n";
    exit(1);
}
