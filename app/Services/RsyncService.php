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
            '-o', 'StrictHostKeyChecking=no',
            '-P', $config['port'],
            sprintf('%s@%s', $config['username'], $config['host']),
        ];
    }

    /**
     * Sync a file from the clean file server using rsync with SSH key authentication.
     * This method uses rsync over SSH with a private key for secure file transfer.
     */
    public function syncFromCleanFileServer(string $remotePath, string $localPath): string
    {
        $config = config('services.clean_file_server');

        $this->ensureDirectoryExists(dirname($localPath));

        Log::info('RsyncService: Starting rsync from clean file server', [
            'host' => $config['host'],
            'user' => $config['username'],
            'remote_path' => $remotePath,
            'local_path' => $localPath,
        ]);

        $command = $this->buildRsyncCommand($config, $remotePath, $localPath);

        // Use a generous timeout for large file transfers
        $result = Process::timeout(300)->run($command);

        if ($result->failed()) {
            Log::error('RsyncService: Rsync from clean file server failed', [
                'exit_code' => $result->exitCode(),
                'error' => $result->errorOutput(),
                'output' => $result->output(),
            ]);

            throw new RuntimeException('Rsync from clean file server failed. Check logs for details.');
        }

        Log::info('RsyncService: Rsync from clean file server completed', ['path' => $localPath]);

        return $localPath;
    }

    /**
     * Build the rsync command with SSH authentication.
     * Uses default SSH key (~/.ssh/id_rsa) for passwordless authentication.
     */
    protected function buildRsyncCommand(array $config, string $remotePath, string $localPath): array
    {
        $sshCommand = sprintf(
            'ssh -o StrictHostKeyChecking=no -p %d',
            $config['port']
        );

        return [
            'rsync',
            '-avz',
            '--no-g',  // Don't preserve group ownership (fixes permission errors)
            '--no-perms',  // Don't preserve permissions
            '-e',
            $sshCommand,
            sprintf('%s@%s:%s', $config['username'], $config['host'], $remotePath),
            $localPath,
        ];
    }


    /**
     * Sync a file from the unscanned file server to the clean file server using rsync.
     * This method connects to the first server (34.81.79.232) and uses rsync to transfer
     * files to the second server (35.194.240.94) via its private IP (192.168.0.10).
     */
    public function syncFromUnscannedToCleanFileServer(string $sourceFilePath, string $targetFilePath): string
    {
        $config = config('services.unscanned_file_server');

        Log::info('RsyncService: Starting rsync from unscanned to clean file server', [
            'source_host' => $config['host'],
            'source_user' => $config['username'],
            'source_path' => $sourceFilePath,
            'target_host' => $config['target_host'],
            'target_user' => $config['target_user'],
            'target_path' => $targetFilePath,
        ]);

        $command = $this->buildUnscannedToCleanRsyncCommand($config, $sourceFilePath, $targetFilePath);

        // Use a generous timeout for large file transfers
        $result = Process::timeout(300)->run($command);

        if ($result->failed()) {
            Log::error('RsyncService: Rsync from unscanned to clean file server failed', [
                'exit_code' => $result->exitCode(),
                'error' => $result->errorOutput(),
                'output' => $result->output(),
            ]);

            throw new RuntimeException('Rsync from unscanned to clean file server failed. Check logs for details.');
        }

        Log::info('RsyncService: Rsync from unscanned to clean file server completed', [
            'target_path' => $targetFilePath,
        ]);

        return $targetFilePath;
    }

    /**
     * Build the rsync command to transfer files from unscanned server to clean server.
     * This command runs on the unscanned server and pushes files to the clean server via private IP.
     */
    protected function buildUnscannedToCleanRsyncCommand(array $config, string $sourceFilePath, string $targetFilePath): array
    {
        // The rsync command that will be executed on the unscanned server
        // This command will push the file to the clean server using its private IP
        $remoteRsyncCommand = sprintf(
            'rsync -avz --no-g --no-perms -e "ssh -o StrictHostKeyChecking=no -p %d" %s %s@%s:%s',
            $config['target_port'],
            escapeshellarg($sourceFilePath),
            $config['target_user'],
            $config['target_host'],
            escapeshellarg($targetFilePath)
        );

        // Build SSH command with key authentication
        $sshCommand = [
            'ssh',
            '-o', 'StrictHostKeyChecking=no',
            '-p', (string) $config['port'],
        ];

        // Add SSH key if specified
        if (!empty($config['ssh_key'])) {
            $sshCommand[] = '-i';
            $sshCommand[] = $config['ssh_key'];
        }

        // Add the user@host and remote command
        $sshCommand[] = sprintf('%s@%s', $config['username'], $config['host']);
        $sshCommand[] = $remoteRsyncCommand;

        return $sshCommand;
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
