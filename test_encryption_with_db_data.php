<?php

/**
 * Test Encryption Process with Database Data
 *
 * This script tests the two-layer encryption process using actual database data format.
 * It simulates the real encryption flow from DataSyncOrchestratorService.
 *
 * Usage: php test_encryption_with_db_data.php
 */

require __DIR__ . '/vendor/autoload.php';

use ZipArchive;

class EncryptionProcessTest
{
    private string $testDir;
    private string $staticKey;
    private string $dynamicKey;
    private array $dbData;

    public function __construct()
    {
        // Use /mnt/tmpzip as configured in the application
        $tmpBasePath = '/mnt/tmpzip';

        // Fallback to /tmp if /mnt/tmpzip doesn't exist or isn't writable
        if (!is_dir($tmpBasePath) || !is_writable($tmpBasePath)) {
            $tmpBasePath = sys_get_temp_dir();
        }

        $this->testDir = $tmpBasePath . '/encryption_db_test_' . time();

        // Create test directory with error handling
        if (!is_dir($this->testDir)) {
            if (!@mkdir($this->testDir, 0777, true)) {
                // If that fails, try current directory
                $this->testDir = __DIR__ . '/encryption_test_' . time();
                if (!@mkdir($this->testDir, 0777, true)) {
                    throw new Exception("Failed to create test directory. Please check permissions.");
                }
            }
        }

        // Load static key from config (simulated)
        $this->staticKey = 'surveillance123@#'; // config('app.zip_encryption_password')

        // Simulate database record data
        $this->dbData = [
            'id' => '019e91c8-0e1f-73f4-94c8-9537d4bded29',
            'task_id' => '019e91c8-0e1e-7084-a2b7-2d4c47757ac0',
            'keywords' => '["色情"]',
            'crawler_machine' => 'bot-3',
            'result_file' => '/mnt/task/2026_06_04_165542_google.zip',
            'file_hash' => 'e3215f6c7898eab342c83fc0b9251d6ccda4831cdfc5fda955d9edab9968247a',
            'crawl_location' => 'https://www.google.com',
            'status' => 'synced',
            'dynamic_key' => null, // Will be generated
            'created_at' => '2026-06-04 16:37:47',
            'updated_at' => '2026-06-04 16:55:43',
        ];

        echo "🧪 Encryption Process Test with Database Data\n";
        echo "Test directory: {$this->testDir}\n";
        echo str_repeat("=", 80) . "\n\n";
    }

    public function run(): void
    {
        try {
            // Display database record
            echo "📊 Database Record:\n";
            echo str_repeat("-", 80) . "\n";
            foreach ($this->dbData as $key => $value) {
                $displayValue = is_null($value) ? 'NULL' : $value;
                echo sprintf("%-20s: %s\n", $key, $displayValue);
            }
            echo str_repeat("-", 80) . "\n\n";

            // Step 1: Create test files (simulating downloaded/unzipped content)
            echo "📁 Step 1: Creating test files (simulating unzipped content)...\n";
            $sourceFolder = $this->createTestFiles();
            echo "✅ Created test files in: {$sourceFolder}\n\n";

            // Step 2: Generate dynamic key (based on final zip path)
            echo "🔑 Step 2: Generating dynamic key...\n";
            $finalZipPath = $this->dbData['result_file'];
            $this->dynamicKey = $this->generateDynamicKey($finalZipPath);
            echo "✅ Dynamic key: {$this->dynamicKey}\n";
            echo "   (Generated from: " . basename($finalZipPath) . " + timestamp + salt)\n\n";

            // Step 3: Layer 1 encryption (dynamic key)
            echo "🔒 Step 3: Layer 1 encryption (dynamic key)...\n";
            $layer1Zip = $this->encryptLayer1($sourceFolder);
            echo "✅ Layer 1 encrypted: {$layer1Zip}\n";
            echo "   Size: " . $this->formatBytes(filesize($layer1Zip)) . "\n";
            echo "   Password: {$this->dynamicKey}\n\n";

            // Step 4: Layer 2 encryption (static key)
            echo "🔒 Step 4: Layer 2 encryption (static key)...\n";
            $finalZip = $this->encryptLayer2($layer1Zip, basename($finalZipPath));
            echo "✅ Layer 2 encrypted: {$finalZip}\n";
            echo "   Size: " . $this->formatBytes(filesize($finalZip)) . "\n";
            echo "   Password: {$this->staticKey}\n\n";

            // Step 5: Verify encryption (try to open without password - should fail)
            echo "🔐 Step 5: Verifying encryption...\n";
            $this->verifyEncryption($finalZip);
            echo "✅ Encryption verified - file is properly encrypted\n\n";

            // Step 6: Test decryption Layer 2 (static key)
            echo "🔓 Step 6: Decrypting Layer 2 (static key)...\n";
            $decryptedLayer1 = $this->decryptLayer2($finalZip);
            echo "✅ Layer 2 decrypted: {$decryptedLayer1}\n\n";

            // Step 7: Test decryption Layer 1 (dynamic key)
            echo "🔓 Step 7: Decrypting Layer 1 (dynamic key)...\n";
            $decryptedFolder = $this->decryptLayer1($decryptedLayer1);
            echo "✅ Layer 1 decrypted: {$decryptedFolder}\n\n";

            // Step 8: Verify files match original
            echo "✔️  Step 8: Verifying files match original...\n";
            $this->verifyFiles($sourceFolder, $decryptedFolder);
            echo "✅ All files verified successfully!\n\n";

            // Summary
            $this->printSummary($finalZip);

            echo "\n✅ TEST PASSED! Encryption process works correctly! 🎉\n";
            echo "\n📝 Database Update:\n";
            echo "   UPDATE crawler_task_items\n";
            echo "   SET dynamic_key = '{$this->dynamicKey}',\n";
            echo "       result_file = '{$finalZipPath}',\n";
            echo "       status = 'synced'\n";
            echo "   WHERE id = '{$this->dbData['id']}';\n\n";

        } catch (Exception $e) {
            echo "\n❌ TEST FAILED: {$e->getMessage()}\n";
            echo "Stack trace:\n{$e->getTraceAsString()}\n";
            exit(1);
        } finally {
            // Cleanup
            echo "\n🧹 Cleaning up test files...\n";
            $this->cleanup();
            echo "✅ Cleanup complete\n";
        }
    }

    private function createTestFiles(): string
    {
        $folder = $this->testDir . '/source_files';

        // Create folder with error handling
        if (!is_dir($folder)) {
            if (!@mkdir($folder, 0777, true)) {
                throw new Exception("Failed to create source files directory: {$folder}");
            }
        }

        // Create test files simulating crawler results
        if (file_put_contents($folder . '/image1.jpg', str_repeat('JPEG_IMAGE_DATA_', 1000)) === false) {
            throw new Exception("Failed to create test file: image1.jpg");
        }
        if (file_put_contents($folder . '/image2.jpg', str_repeat('JPEG_IMAGE_DATA_', 1000)) === false) {
            throw new Exception("Failed to create test file: image2.jpg");
        }
        if (file_put_contents($folder . '/image3.jpg', str_repeat('JPEG_IMAGE_DATA_', 1000)) === false) {
            throw new Exception("Failed to create test file: image3.jpg");
        }

        if (file_put_contents($folder . '/metadata.json', json_encode([
            'task_id' => $this->dbData['task_id'],
            'keywords' => json_decode($this->dbData['keywords']),
            'crawl_location' => $this->dbData['crawl_location'],
            'timestamp' => $this->dbData['created_at'],
            'images' => ['image1.jpg', 'image2.jpg', 'image3.jpg']
        ], JSON_PRETTY_PRINT)) === false) {
            throw new Exception("Failed to create test file: metadata.json");
        }

        return $folder;
    }

    /**
     * Generate dynamic key exactly as DataSyncOrchestratorService does
     */
    private function generateDynamicKey(string $filePath): string
    {
        $fileName = basename($filePath);
        $timestamp = time(); // Using current timestamp
        $salt = 'surveillance_salt_2024'; // config('app.encryption_salt')

        // Generate unique key: hash(filename + timestamp + salt)
        return hash('sha256', $fileName . $timestamp . $salt);
    }

    private function encryptLayer1(string $sourceFolder): string
    {
        $zipPath = $this->testDir . '/layer1_encrypted.zip';
        $this->createEncryptedZip($sourceFolder, $zipPath, $this->dynamicKey);
        return $zipPath;
    }

    private function encryptLayer2(string $layer1Zip, string $finalFileName): string
    {
        // Create temp folder with the inner zip
        $tempFolder = $this->testDir . '/layer2_temp';
        mkdir($tempFolder, 0755, true);

        // Rename to {name}_encrypted.zip (as done in DataSyncOrchestratorService)
        $dirPath = pathinfo($finalFileName, PATHINFO_FILENAME);
        $innerZipName = $dirPath . '_encrypted.zip';
        $innerZipPath = $tempFolder . '/' . $innerZipName;
        copy($layer1Zip, $innerZipPath);

        echo "   Created inner zip: {$innerZipName}\n";
        echo "   Inner zip path: {$innerZipPath}\n";
        echo "   Inner zip exists: " . (file_exists($innerZipPath) ? 'YES' : 'NO') . "\n";

        // Encrypt the folder containing the inner zip
        $finalZip = $this->testDir . '/final_encrypted.zip';
        $this->createEncryptedZip($tempFolder, $finalZip, $this->staticKey);

        return $finalZip;
    }

    private function verifyEncryption(string $zipPath): void
    {
        $zip = new ZipArchive();

        // Try to open without password
        if ($zip->open($zipPath) === true) {
            // Try to extract without password
            $testExtract = $this->testDir . '/test_extract';
            mkdir($testExtract, 0755, true);

            $result = @$zip->extractTo($testExtract);
            $zip->close();

            if ($result) {
                throw new Exception("Security issue: ZIP extracted without password!");
            }

            // Clean up
            $this->removeDirectory($testExtract);
        }

        echo "   ✓ Cannot extract without password\n";

        // Verify with wrong password
        if ($zip->open($zipPath) === true) {
            $zip->setPassword('wrong_password');
            $testExtract = $this->testDir . '/test_extract_wrong';
            mkdir($testExtract, 0755, true);

            $result = @$zip->extractTo($testExtract);
            $zip->close();

            if ($result) {
                throw new Exception("Security issue: ZIP extracted with wrong password!");
            }

            // Clean up
            $this->removeDirectory($testExtract);
        }

        echo "   ✓ Cannot extract with wrong password\n";
    }

    private function decryptLayer2(string $finalZip): string
    {
        $extractPath = $this->testDir . '/layer2_decrypted';
        mkdir($extractPath, 0755, true);

        $this->extractEncryptedZip($finalZip, $extractPath, $this->staticKey);

        // Find the inner encrypted zip - need to search recursively
        $innerZip = null;

        echo "   Files in decrypted Layer 2:\n";

        // Use recursive iterator to find all files
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relativePath = substr($file->getPathname(), strlen($extractPath) + 1);
            $type = $file->isDir() ? '[DIR]' : '[FILE]';
            echo "      {$type} {$relativePath}\n";

            if ($file->isFile() && str_ends_with($file->getFilename(), '_encrypted.zip')) {
                $innerZip = $file->getPathname();
            }
        }

        if (!$innerZip || !file_exists($innerZip)) {
            throw new Exception("Inner encrypted zip not found after Layer 2 decryption. Expected file ending with '_encrypted.zip'");
        }

        echo "   Found inner zip: " . basename($innerZip) . "\n";
        return $innerZip;
    }

    private function decryptLayer1(string $layer1Zip): string
    {
        $extractPath = $this->testDir . '/layer1_decrypted';
        mkdir($extractPath, 0755, true);

        $this->extractEncryptedZip($layer1Zip, $extractPath, $this->dynamicKey);

        return $extractPath;
    }

    private function createEncryptedZip(string $sourcePath, string $zipPath, string $password): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Failed to create ZIP: {$zipPath}");
        }

        $zip->setPassword($password);

        if (is_file($sourcePath)) {
            // Single file
            $zip->addFile($sourcePath, basename($sourcePath));
            $zip->setEncryptionName(basename($sourcePath), ZipArchive::EM_AES_256);
        } else {
            // Directory - normalize path
            $sourcePath = rtrim($sourcePath, '/');

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($sourcePath) + 1);

                    $zip->addFile($filePath, $relativePath);
                    $zip->setEncryptionName($relativePath, ZipArchive::EM_AES_256);
                }
            }
        }

        $zip->close();

        if (!file_exists($zipPath)) {
            throw new Exception("ZIP file was not created: {$zipPath}");
        }
    }

    private function extractEncryptedZip(string $zipPath, string $extractPath, string $password): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new Exception("Failed to open ZIP: {$zipPath}");
        }

        $zip->setPassword($password);

        if (!$zip->extractTo($extractPath)) {
            $zip->close();
            throw new Exception("Failed to extract ZIP with provided password");
        }

        $zip->close();
    }

    private function verifyFiles(string $sourceFolder, string $decryptedFolder): void
    {
        $sourceFiles = $this->getFileList($sourceFolder);
        $decryptedFiles = $this->getFileList($decryptedFolder);

        echo "   Source files (" . count($sourceFiles) . "):\n";
        foreach ($sourceFiles as $path => $file) {
            echo "      - {$path}\n";
        }

        echo "   Decrypted files (" . count($decryptedFiles) . "):\n";
        foreach ($decryptedFiles as $path => $file) {
            echo "      - {$path}\n";
        }

        if (count($sourceFiles) !== count($decryptedFiles)) {
            throw new Exception(
                "File count mismatch: " .
                count($sourceFiles) . " source files vs " .
                count($decryptedFiles) . " decrypted files"
            );
        }

        foreach ($sourceFiles as $relativePath => $sourceFile) {
            if (!isset($decryptedFiles[$relativePath])) {
                throw new Exception("Missing file after decryption: {$relativePath}");
            }

            $sourceContent = file_get_contents($sourceFile);
            $decryptedContent = file_get_contents($decryptedFiles[$relativePath]);

            if ($sourceContent !== $decryptedContent) {
                throw new Exception("Content mismatch for file: {$relativePath}");
            }

            echo "   ✓ {$relativePath} - OK\n";
        }
    }

    private function getFileList(string $folder): array
    {
        $files = [];

        // Normalize folder path (remove trailing slash)
        $folder = rtrim($folder, '/');

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($folder) + 1);
                $files[$relativePath] = $filePath;
            }
        }

        return $files;
    }

    private function printSummary(string $finalZip): void
    {
        echo str_repeat("=", 80) . "\n";
        echo "📊 ENCRYPTION SUMMARY\n";
        echo str_repeat("=", 80) . "\n";
        echo "Database Record ID:    {$this->dbData['id']}\n";
        echo "Task ID:               {$this->dbData['task_id']}\n";
        echo "Keywords:              {$this->dbData['keywords']}\n";
        echo "Crawl Location:        {$this->dbData['crawl_location']}\n";
        echo str_repeat("-", 80) . "\n";
        echo "Static Key (Layer 2):  {$this->staticKey}\n";
        echo "Dynamic Key (Layer 1): {$this->dynamicKey}\n";
        echo str_repeat("-", 80) . "\n";
        echo "Final Encrypted File:  {$this->dbData['result_file']}\n";
        echo "File Size:             " . $this->formatBytes(filesize($finalZip)) . "\n";
        echo "File Hash (SHA256):    {$this->dbData['file_hash']}\n";
        echo str_repeat("=", 80) . "\n";
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function cleanup(): void
    {
        $this->removeDirectory($this->testDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}

// Run the test
echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                  ENCRYPTION PROCESS TEST WITH DATABASE DATA                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$test = new EncryptionProcessTest();
$test->run();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                              TEST COMPLETED                                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n";
