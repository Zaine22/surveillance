<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class CleanFileServerPollingService
{
    /**
     * Wait for a file to appear on the CleanFileServer
     * Polls every 30 seconds for up to 10 minutes
     *
     * @param string $filename The filename to wait for (e.g., result_019e72eb.zip)
     * @param int $maxWaitSeconds Maximum time to wait (default: 600 = 10 minutes)
     * @param int $pollIntervalSeconds How often to check (default: 30 seconds)
     * @return bool True if file exists, false if timeout
     */
    public function waitForFile(string $filename, int $maxWaitSeconds = 600, int $pollIntervalSeconds = 30): bool
    {
        $cleanServerPath = '/home/rsyncbot/clean-files/' . $filename;
        $cleanServerHost = config('services.clean_file_server.host', '35.194.240.94');
        $cleanServerUser = config('services.clean_file_server.user', 'user3');
        $sshKeyPath = config('services.clean_file_server.ssh_key', '/var/www/.ssh/id_rsa');

        $startTime = time();
        $attempts = 0;

        Log::info('Waiting for file on CleanFileServer', [
            'filename' => $filename,
            'path' => $cleanServerPath,
            'max_wait_seconds' => $maxWaitSeconds,
            'poll_interval' => $pollIntervalSeconds,
        ]);

        while ((time() - $startTime) < $maxWaitSeconds) {
            $attempts++;

            // Check if file exists on CleanFileServer via SSH
            $command = sprintf(
                'ssh -i %s -o StrictHostKeyChecking=no -o ConnectTimeout=10 %s@%s "test -f %s && echo EXISTS || echo NOT_FOUND"',
                escapeshellarg($sshKeyPath),
                escapeshellarg($cleanServerUser),
                escapeshellarg($cleanServerHost),
                escapeshellarg($cleanServerPath)
            );

            $result = Process::run($command);

            if ($result->successful() && trim($result->output()) === 'EXISTS') {
                $elapsedTime = time() - $startTime;
                Log::info('File found on CleanFileServer', [
                    'filename' => $filename,
                    'attempts' => $attempts,
                    'elapsed_seconds' => $elapsedTime,
                ]);
                return true;
            }

            if ($attempts === 1 || $attempts % 5 === 0) {
                Log::info('File not yet available on CleanFileServer', [
                    'filename' => $filename,
                    'attempt' => $attempts,
                    'elapsed_seconds' => time() - $startTime,
                    'max_wait_seconds' => $maxWaitSeconds,
                ]);
            }

            // Wait before next poll
            sleep($pollIntervalSeconds);
        }

        // Timeout reached
        Log::warning('Timeout waiting for file on CleanFileServer', [
            'filename' => $filename,
            'attempts' => $attempts,
            'elapsed_seconds' => time() - $startTime,
        ]);

        return false;
    }

    /**
     * Check if a file exists on CleanFileServer (single check, no waiting)
     *
     * @param string $filename The filename to check
     * @return bool True if file exists, false otherwise
     */
    public function fileExists(string $filename): bool
    {
        $cleanServerPath = '/home/rsyncbot/clean-files/' . $filename;
        $cleanServerHost = config('services.clean_file_server.host', '35.194.240.94');
        $cleanServerUser = config('services.clean_file_server.user', 'user3');
        $sshKeyPath = config('services.clean_file_server.ssh_key', '/var/www/.ssh/id_rsa');

        $command = sprintf(
            'ssh -i %s -o StrictHostKeyChecking=no -o ConnectTimeout=10 %s@%s "test -f %s && echo EXISTS || echo NOT_FOUND"',
            escapeshellarg($sshKeyPath),
            escapeshellarg($cleanServerUser),
            escapeshellarg($cleanServerHost),
            escapeshellarg($cleanServerPath)
        );

        $result = Process::run($command);

        return $result->successful() && trim($result->output()) === 'EXISTS';
    }
}