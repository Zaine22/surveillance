<?php

/**
 * Test Two-Layer Encryption System
 *
 * This script tests the complete two-layer encryption and decryption process:
 * 1. Create test files
 * 2. Encrypt with dynamic key (Layer 1)
 * 3. Encrypt with static key (Layer 2)
 * 4. Decrypt Layer 2 (static key)
 * 5. Decrypt Layer 1 (dynamic key)
 * 6. Verify files match original
 */

require __DIR__ . '/vendor/autoload.php';

use ZipArchive;

class TwoLayerEncryptionTest
{
    private string $testDir;
    private string $staticKey = 'surveillance123@#';
    private string $dynamicKey;

    public function __construct()
    {
        $this->testDir = sys_get_temp_dir() . '/encryption_test_' . time();
        mkdir($this->testDir, 0755, true);

        echo "🧪 Two-Layer Encryption Test\n";
        echo "Test directory: {$this->testDir}\n";
        echo str_repeat("=", 60) . "\n\n";
    }

    public function run(): void
    {
        try {
            // Step 1: Create test files
            echo "📁 Step 1: Creating test files...\n";
            $sourceFolder = $this->createTestFiles();
            echo "✅ Created test files in: {$sourceFolder}\n\n";

            // Step 2: Generate dynamic key
            echo "🔑 Step 2: Generating dynamic key...\n";
            $this->dynamicKey = $this->generateDynamicKey('test_file.zip');
            echo "✅ Dynamic key: {$this->dynamicKey}\n\n";

            // Step 3: Layer 1 encryption (dynamic key)
            echo "🔒 Step 3: Layer 1 encryption (dynamic key)...\n";
            $layer1Zip = $this->encryptLayer1($sourceFolder);
            echo "✅ Layer 1 encrypted: {$layer1Zip}\n";
            echo "   Size: " . $this->formatBytes(filesize($layer1Zip)) . "\n\n";

            // Step 4: Layer 2 encryption (static key)
            echo "🔒 Step 4: Layer 2 encryption (static key)...\n";
            $finalZip = $this->encryptLayer2($layer1Zip);
            echo "✅ Layer 2 encrypted: {$finalZip}\n";
            echo "   Size: " . $this->formatBytes(filesize($finalZip)) . "\n\n";

            // Step 5: Decrypt Layer 2 (static key)
            echo "🔓 Step 5: Decrypting Layer 2 (static key)...\n";
            $decryptedLayer1 = $this->decryptLayer2($finalZip);
            echo "✅ Layer 2 decrypted: {$decryptedLayer1}\n\n";

            // Step 6: Decrypt Layer 1 (dynamic key)
            echo "🔓 Step 6: Decrypting Layer 1 (dynamic key)...\n";
            $decryptedFolder = $this->decryptLayer1($decryptedLayer1);
            echo "✅ Layer 1 decrypted: {$decryptedFolder}\n\n";

            // Step 7: Verify files
            echo "✔️  Step 7: Verifying files...\n";
            $this->verifyFiles($sourceFolder, $decryptedFolder);
            echo "✅ All files verified successfully!\n\n";

            // Summary
            $this->printSummary($finalZip);

            echo "\n✅ TEST PASSED! Two-layer encryption works correctly! 🎉\n";

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
        mkdir($folder, 0755, true);

        // Create test files with different content
        file_put_contents($folder . '/test1.txt', 'This is test file 1 - ' . date('Y-m-d H:i:s'));
        file_put_contents($folder . '/test2.txt', 'This is test file 2 - ' . str_repeat('Hello World! ', 100));
        file_put_contents($folder . '/test3.json', json_encode([
            'name' => 'Test Data',
            'timestamp' => time(),
            'data' => range(1, 100)
        ], JSON_PRETTY_PRINT));

        // Create subfolder
        mkdir($folder . '/subfolder', 0755, true);
        file_put_contents($folder . '/subfolder/nested.txt', 'Nested file content');

        return $folder;
    }

    private function generateDynamicKey(string $fileName): string
    {
        $timestamp = time();
        $salt = 'surveillance_salt_2024';

        return hash('sha256', $fileName . $timestamp . $salt);
    }

    private function encryptLayer1(string $sourceFolder): string
    {
        $zipPath = $this->testDir . '/layer1_encrypted.zip';
        $this->createEncryptedZip($sourceFolder, $zipPath, $this->dynamicKey);
        return $zipPath;
    }

    private function encryptLayer2(string $layer1Zip): string
    {
        // Create temp folder with the inner zip
        $tempFolder = $this->testDir . '/layer2_temp';
        mkdir($tempFolder, 0755, true);

        // Rename to {name}_encrypted.zip
        $innerZipName = 'test_file_encrypted.zip';
        copy($layer1Zip, $tempFolder . '/' . $innerZipName);

        // Encrypt the folder containing the inner zip
        $finalZip = $this->testDir . '/final_encrypted.zip';
        $this->createEncryptedZip($tempFolder, $finalZip, $this->staticKey);

        return $finalZip;
    }

    private function decryptLayer2(string $finalZip): string
    {
        $extractPath = $this->testDir . '/layer2_decrypted';
        mkdir($extractPath, 0755, true);

        $this->extractEncryptedZip($finalZip, $extractPath, $this->staticKey);

        // Find the inner encrypted zip
        $innerZip = $extractPath . '/test_file_encrypted.zip';
        if (!file_exists($innerZip)) {
            throw new Exception("Inner encrypted zip not found after Layer 2 decryption");
        }

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
            // Directory
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourcePath),
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
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder),
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
        echo str_repeat("=", 60) . "\n";
        echo "📊 ENCRYPTION SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        echo "Static Key (Layer 2):  {$this->staticKey}\n";
        echo "Dynamic Key (Layer 1): {$this->dynamicKey}\n";
        echo "Final Encrypted File:  {$finalZip}\n";
        echo "File Size:             " . $this->formatBytes(filesize($finalZip)) . "\n";
        echo str_repeat("=", 60) . "\n";
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
$test = new TwoLayerEncryptionTest();
$test->run();
