<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use ZipArchive;

class MediaUrlService
{
    /**
     * Ensure media is available by extracting from encrypted zip if tmp folder is missing
     *
     * @param string $mediaUrl The media URL to check
     * @return string The same media URL (after ensuring availability)
     * @throws \RuntimeException
     */
    public function ensureMediaAvailable(string $mediaUrl): string
    {
        // Check if auto-extract is enabled
        if (!config('app.auto_extract_on_demand', true)) {
            return $mediaUrl;
        }

        // Parse URL to extract tmp path
        // Example: http://220.130.187.241:9680/mnt/tmp/2026_06_04_101234_example/image1.jpg
        $tmpPath = $this->extractTmpPathFromUrl($mediaUrl);

        if (!$tmpPath) {
            // Not a tmp path, return as is
            return $mediaUrl;
        }

        // Extract folder name from path
        // Example: /tmpzip/2026_06_04_101234_example/image1.jpg -> 2026_06_04_101234_example
        $folderName = $this->extractFolderName($tmpPath);

        if (!$folderName) {
            return $mediaUrl;
        }

        // Use configured tmpzip path
        $tmpBasePath = config('app.tmp_zip_path', '/mnt/tmpzip');
        $tmpFolder = $tmpBasePath . '/' . $folderName;

        // Check if tmp folder exists
        if (is_dir($tmpFolder)) {
            // Folder exists, media is available
            return $mediaUrl;
        }

        // Folder missing, extract from encrypted zip
        Log::info('Tmp folder missing, extracting from encrypted zip', [
            'media_url' => $mediaUrl,
            'folder_name' => $folderName,
            'tmp_folder' => $tmpFolder,
        ]);

        $this->extractFromEncryptedZip($folderName);

        // Optionally reset cleanup timer
        if (config('app.reset_cleanup_timer_on_access', false)) {
            $this->resetCleanupTimer($folderName);
        }

        return $mediaUrl;
    }

    /**
     * Extract tmp path from media URL
     *
     * @param string $url The media URL
     * @return string|null The tmp path or null if not a tmp URL
     */
    protected function extractTmpPathFromUrl(string $url): ?string
    {
        // Parse URL to get path component
        $path = parse_url($url, PHP_URL_PATH);

        if (!$path) {
            return null;
        }

        // Check if path contains /tmpzip/
        // Example: /tmpzip/2026_06_04_101234_example/image1.jpg
        if (preg_match('#/tmpzip/([^/]+)/#', $path, $matches)) {
            return $path;
        }

        return null;
    }

    /**
     * Extract folder name from tmp path
     *
     * @param string $tmpPath The tmp path
     * @return string|null The folder name or null
     */
    protected function extractFolderName(string $tmpPath): ?string
    {
        // Extract folder name from path
        // Example: /tmpzip/2026_06_04_101234_example/image1.jpg -> 2026_06_04_101234_example
        if (preg_match('#/tmpzip/([^/]+)/#', $tmpPath, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract files from encrypted zip to tmp folder
     *
     * @param string $folderName The folder name (without .zip extension)
     * @return void
     * @throws \RuntimeException
     */
    protected function extractFromEncryptedZip(string $folderName): void
    {
        $zipPath = '/mnt/task/' . $folderName . '.zip';

        // Use configured tmpzip path
        $tmpBasePath = config('app.tmp_zip_path', '/mnt/tmpzip');
        $tmpFolder = $tmpBasePath . '/' . $folderName;

        if (!file_exists($zipPath)) {
            throw new \RuntimeException("Encrypted zip not found: {$zipPath}");
        }

        Log::info('Extracting from encrypted zip', [
            'zip_path' => $zipPath,
            'tmp_folder' => $tmpFolder,
        ]);

        $zip = new ZipArchive();
        $result = $zip->open($zipPath);

        if ($result !== true) {
            throw new \RuntimeException("Failed to open encrypted zip: {$zipPath}");
        }

        try {
            // Set password for decryption
            $password = config('app.zip_encryption_password', 'default_secure_password_2024');
            $zip->setPassword($password);

            // Create tmp folder if it doesn't exist
            if (!is_dir($tmpFolder)) {
                mkdir($tmpFolder, 0755, true);
            }

            // Extract all files
            if (!$zip->extractTo($tmpFolder)) {
                throw new \RuntimeException("Failed to extract encrypted zip to: {$tmpFolder}");
            }

            $fileCount = $zip->numFiles;
            $zip->close();

            Log::info('Successfully extracted from encrypted zip', [
                'zip_path' => $zipPath,
                'tmp_folder' => $tmpFolder,
                'file_count' => $fileCount,
            ]);

        } catch (\Throwable $e) {
            $zip->close();

            Log::error('Failed to extract from encrypted zip', [
                'zip_path' => $zipPath,
                'tmp_folder' => $tmpFolder,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException("Failed to extract from encrypted zip: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Reset cleanup timer by rescheduling the cleanup job
     *
     * @param string $folderName The folder name
     * @return void
     */
    protected function resetCleanupTimer(string $folderName): void
    {
        $retentionDays = config('app.tmp_file_retention_days', 30);
        $cleanupDate = now()->addDays($retentionDays);

        // Dispatch new cleanup job
        \App\Jobs\CleanupTmpFilesJob::dispatch($folderName, $cleanupDate)
            ->delay($cleanupDate);

        Log::info('Reset cleanup timer for accessed folder', [
            'folder_name' => $folderName,
            'new_cleanup_date' => $cleanupDate,
        ]);
    }

    /**
     * Batch ensure media availability for multiple URLs
     *
     * @param array $mediaUrls Array of media URLs
     * @return array The same array of media URLs (after ensuring availability)
     */
    public function ensureMultipleMediaAvailable(array $mediaUrls): array
    {
        // Group URLs by folder name to avoid multiple extractions
        $folderNames = [];

        foreach ($mediaUrls as $url) {
            $tmpPath = $this->extractTmpPathFromUrl($url);
            if ($tmpPath) {
                $folderName = $this->extractFolderName($tmpPath);
                if ($folderName && !isset($folderNames[$folderName])) {
                    $folderNames[$folderName] = true;
                    $this->ensureMediaAvailable($url);
                }
            }
        }

        return $mediaUrls;
    }
}