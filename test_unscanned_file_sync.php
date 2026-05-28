<?php

/**
 * Test syncing files from unscanned file server to clean file server
 * This tests the transfer from 34.81.79.232 to 35.194.240.94 (via private IP 192.168.0.10)
 * Run with: php test_unscanned_file_sync.php
 */

require __DIR__ . '/vendor/autoload.php';

use App\Services\RsyncService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Unscanned File Sync ===\n\n";

// The file on the unscanned file server (34.81.79.232)
$sourceFilePath = '/home/rsyncbot/unscann-files/file.zip';
// Target path on the clean file server (35.194.240.94)
$targetFilePath = '/home/rsyncbot/clean-files/file.zip';

echo "Configuration:\n";
echo "  Source server: 34.81.79.232\n";
echo "  Source file: {$sourceFilePath}\n";
echo "  Target server: 35.194.240.94 (via private IP 192.168.0.10)\n";
echo "  Target file: {$targetFilePath}\n\n";

try {
    $rsyncService = app(RsyncService::class);

    echo "Starting rsync transfer from unscanned to clean server...\n";
    echo "This will:\n";
    echo "  1. SSH into 34.81.79.232\n";
    echo "  2. Execute rsync on that server to push file to 192.168.0.10\n";
    echo "  3. File will be saved to {$targetFilePath}\n\n";

    $result = $rsyncService->syncFromUnscannedToCleanFileServer($sourceFilePath, $targetFilePath);

    echo "✓ Transfer successful!\n\n";
    echo "File Details:\n";
    echo "  Transferred to: {$result}\n";
    echo "  Target server: 35.194.240.94\n";
    echo "  Target path: {$targetFilePath}\n";

    echo "\n✓ Test completed successfully!\n";
    echo "\nThe file has been transferred from:\n";
    echo "  34.81.79.232:{$sourceFilePath}\n";
    echo "to:\n";
    echo "  35.194.240.94:{$targetFilePath}\n";
    echo "\nYou can now use this in your application with:\n";
    echo "  \$service->syncFromUnscannedToCleanFileServer('{$sourceFilePath}', '{$targetFilePath}');\n";

} catch (\Exception $e) {
    echo "✗ Transfer failed!\n";
    echo "Error: {$e->getMessage()}\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    
    echo "\nTroubleshooting:\n";
    echo "  1. Ensure SSH keys are set up between servers\n";
    echo "  2. Verify the source file exists on 34.81.79.232\n";
    echo "  3. Check that 34.81.79.232 can access 35.194.240.94 via private IP 192.168.0.10\n";
    echo "  4. Verify rsyncbot user has proper permissions on both servers\n";
    echo "  5. Check that the target directory exists: /home/rsyncbot/clean-files/\n";
    
    exit(1);
}