<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class RsyncService
{
    public function __construct() {}

    public function sync(string $source, string $target): string
    {
        Log::info('Starting local rsync', ['source' => $source, 'target' => $target]);
        $result = Process::run(['rsync', '-avz', $source, $target]);

        if ($result->failed()) {
            Log::error('Local rsync failed', [
                'error' => $result->errorOutput(),
                'output' => $result->output(),
            ]);
            throw new RuntimeException('Rsync failed: '.$result->errorOutput());
        }

        Log::info('Local rsync completed', ['target' => $target]);

        return $target;
    }

    /**
     * Sync a crawler file from the remote server to the local project.
     */
    public function syncCrawlerFileToNas(string $remotePath, string $localPath): string
    {
        $config = config('services.crawler');

        // Ensure the local directory exists
        if (! is_dir($dir = dirname($localPath))) {
            mkdir($dir, 0755, true);
        }

        $remoteSource = sprintf(
            '%s@%s',
            $config['username'],
            $config['host']
        );

        // We use sftp instead of rsync because the remote user is restricted to nologin (SFTP only).
        // The batch-mode (-b -) allows us to pipe commands to sftp.
        // We add StrictHostKeyChecking=no to avoid hanging on new servers.
        $command = [
            'sshpass',
            '-p',
            $config['password'],
            'sftp',
            '-o',
            'StrictHostKeyChecking=no',
            '-o',
            'ConnectTimeout=30',
            $remoteSource,
        ];

        // The sftp commands to execute
        $sftpCommands = sprintf("get %s %s\nquit", $remotePath, $localPath);

        Log::info('Starting remote sftp download', [
            'remote_path' => $remotePath,
            'local_path' => $localPath,
            'user' => $config['username'],
            'host' => $config['host'],
        ]);

        $result = Process::input($sftpCommands)->timeout(300)->run($command);

        if ($result->failed()) {
            Log::error('Remote sftp failed', [
                'error' => $result->errorOutput(),
                'output' => $result->output(),
                'exit_code' => $result->exitCode(),
            ]);

            throw new RuntimeException('Remote sftp failed: '.$result->errorOutput());
        }

        Log::info('Remote sftp completed', ['local_path' => $localPath]);

        return $localPath;
    }
}
