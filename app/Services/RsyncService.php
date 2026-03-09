<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class RsyncService
{
    /**
     * Sync a file locally using rsync.
     */
    public function sync(string $source, string $target): string
    {
        Log::info('RsyncService: Starting local sync', ['source' => $source, 'target' => $target]);

        $result = Process::run(['rsync', '-avz', $source, $target]);

        if ($result->failed()) {
            throw new RuntimeException(sprintf(
                'Local rsync failed (Exit: %d): %s',
                $result->exitCode(),
                $result->errorOutput() ?: $result->output()
            ));
        }

        Log::info('RsyncService: Local sync completed', ['target' => $target]);

        return $target;
    }

    /**
     * Sync a file from a remote server using SFTP.
     * This is used when the remote user is restricted to SFTP-only access.
     */
    public function syncCrawlerFileToNas(string $remotePath, string $localPath): string
    {
        $config = config('services.crawler');

        $this->ensureDirectoryExists(dirname($localPath));

        Log::info('RsyncService: Starting remote SFTP download', [
            'host' => $config['host'],
            'user' => $config['username'],
            'password' => $config['password'],
            'remote_path' => $remotePath,
            'local_path' => $localPath,
        ]);

        $command = $this->buildSftpCommand($config);
        $batch = sprintf("get %s %s\nquit", $remotePath, $localPath);

        // We use a generous timeout for large file transfers (matched to Horizon worker timeout)
        $result = Process::input($batch)
            ->timeout(300)
            ->run($command);

        if ($result->failed()) {
            Log::error('RsyncService: Remote SFTP failed', [
                'exit_code' => $result->exitCode(),
                'error' => $result->errorOutput(),
                'output' => $result->output(),
            ]);

            throw new RuntimeException('Remote SFTP transfer failed. Check logs for details.');
        }

        Log::info('RsyncService: Remote SFTP download completed', ['path' => $localPath]);

        return $localPath;
    }

    /**
     * Build the sftp command with necessary options for automated background execution.
     */
    protected function buildSftpCommand(array $config): array
    {
        return [
            'sshpass',
            '-p',
            $config['password'],
            'sftp',
            '-o', 'BatchMode=yes',
            '-o', 'StrictHostKeyChecking=no',
            '-o', 'UserKnownHostsFile=/dev/null',
            '-o', 'ConnectTimeout=30',
            sprintf('%s@%s', $config['username'], $config['host']),
        ];
    }

    /**
     * Ensure the target directory exists and is writable.
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (! is_dir($directory)) {
            if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }
    }
}
