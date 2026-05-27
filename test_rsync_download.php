<?php

/**
 * Test downloading the uploaded test.zip file from clean file server
 * Run with: php test_rsync_download.php
 */

require __DIR__ . '/vendor/autoload.php';

use App\Services\RsyncService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Rsync Download of test.zip ===\n\n";

// The file you uploaded
$remotePath = '/tmp/test.zip';
$localPath = '/tmp/downloaded_test.zip';

echo "Configuration:\n";
echo "  Remote file: {$remotePath}\n";
echo "  Local destination: {$localPath}\n\n";

try {
    $rsyncService = app(RsyncService::class);

    echo "Starting rsync download...\n";
    $result = $rsyncService->syncFromCleanFileServer($remotePath, $localPath);

    echo "✓ Download successful!\n\n";
    echo "File Details:\n";
    echo "  Saved to: {$result}\n";
    echo "  File size: " . number_format(filesize($result)) . " bytes (" . round(filesize($result) / 1024 / 1024, 2) . " MB)\n";

    // Verify it's a valid ZIP file
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        $res = $zip->open($result);
        if ($res === true) {
            echo "  ZIP file is valid\n";
            echo "  Number of files in ZIP: " . $zip->numFiles . "\n";

            // Show first few files
            echo "\n  Contents (first 10 files):\n";
            for ($i = 0; $i < min(10, $zip->numFiles); $i++) {
                $stat = $zip->statIndex($i);
                echo "    - " . $stat['name'] . " (" . number_format($stat['size']) . " bytes)\n";
            }
            if ($zip->numFiles > 10) {
                echo "    ... and " . ($zip->numFiles - 10) . " more files\n";
            }

            $zip->close();
        } else {
            echo "  Warning: Could not open as ZIP file\n";
        }
    }

    echo "\n✓ Test completed successfully!\n";
    echo "\nYou can now use this in your application with:\n";
    echo "  \$service->syncFromCleanFileServer('/tmp/test.zip', '/mnt/task/test.zip');\n";

} catch (\Exception $e) {
    echo "✗ Download failed!\n";
    echo "Error: {$e->getMessage()}\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
