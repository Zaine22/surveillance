<?php

/**
 * Test complete file sync: FileServer → CleanFileServer → MainWeb
 * This tests the full two-hop transfer from 34.81.79.232 to 35.194.240.94 to 220.130.187.241
 * Run with: php test_complete_file_sync.php
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\CrawlerTaskItem;
use App\Services\DataSyncOrchestratorService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Complete File Sync (FileServer → CleanFileServer → MainWeb) ===\n\n";

// Create a test CrawlerTaskItem
$testUrl = 'http://34.81.79.232/home/rsyncbot/unscann-files/file.zip';

echo "Configuration:\n";
echo "  Source: FileServer (34.81.79.232)\n";
echo "  Source file: /home/rsyncbot/unscann-files/file.zip\n";
echo "  Intermediate: CleanFileServer (35.194.240.94 / 192.168.0.10)\n";
echo "  Intermediate path: /home/rsyncbot/clean-files/file.zip\n";
echo "  Final destination: MainWeb (220.130.187.241)\n";
echo "  Final path: /mnt/task/[generated_filename].zip\n\n";

try {
    echo "Starting complete file sync...\n";
    echo "This will:\n";
    echo "  1. SSH into FileServer (34.81.79.232)\n";
    echo "  2. Execute rsync to push file to CleanFileServer (192.168.0.10)\n";
    echo "  3. Transfer file from CleanFileServer to MainWeb\n\n";

    $rsyncService = app(\App\Services\RsyncService::class);

    // Step 1: Transfer from FileServer to CleanFileServer
    echo "Step 1: Syncing from FileServer to CleanFileServer...\n";
    $sourcePath = '/home/rsyncbot/unscann-files/file.zip';
    $cleanServerPath = '/home/rsyncbot/clean-files/file.zip';

    $rsyncService->syncFromUnscannedToCleanFileServer($sourcePath, $cleanServerPath);
    echo "✓ Step 1 completed: File transferred to CleanFileServer\n\n";

    // Step 2: Transfer from CleanFileServer to MainWeb
    echo "Step 2: Syncing from CleanFileServer to MainWeb...\n";
    $mainWebPath = '/mnt/task/test_' . date('Y_m_d_His') . '.zip';

    $rsyncService->syncFromCleanFileServer($cleanServerPath, $mainWebPath);
    echo "✓ Step 2 completed: File transferred to MainWeb\n\n";

    $result = $mainWebPath;

    echo "✓ Complete sync successful!\n\n";
    echo "File Details:\n";
    echo "  Final path: {$result}\n";
    echo "  File size: " . number_format(filesize($result)) . " bytes (" . round(filesize($result) / 1024 / 1024, 2) . " MB)\n";

    // Check if file was unzipped
    $unzippedDir = dirname($result) . '/' . basename($result, '.zip');
    if (is_dir($unzippedDir)) {
        echo "  Unzipped to: {$unzippedDir}\n";
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($unzippedDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $fileCount = iterator_count($files);
        echo "  Extracted files: {$fileCount}\n";
    }

    echo "\n✓ Test completed successfully!\n";
    echo "\nThe file has been transferred through the complete chain:\n";
    echo "  34.81.79.232 → 35.194.240.94 → 220.130.187.241\n";
    echo "\nYou can now use this in your application with:\n";
    echo "  \$service->syncUnscannedFileToMainWeb(\$crawlerTaskItem);\n";

} catch (\Exception $e) {
    echo "✗ Complete sync failed!\n";
    echo "Error: {$e->getMessage()}\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";

    echo "\nTroubleshooting:\n";
    echo "  1. Ensure SSH keys are set up between all servers\n";
    echo "  2. Verify the source file exists on 34.81.79.232\n";
    echo "  3. Check that 34.81.79.232 can access 35.194.240.94 via private IP 192.168.0.10\n";
    echo "  4. Verify MainWeb (220.130.187.241) can access CleanFileServer (35.194.240.94)\n";
    echo "  5. Check that all directories exist and have proper permissions\n";
    echo "  6. Review Laravel logs: tail -f storage/logs/laravel.log\n";

    exit(1);
}
