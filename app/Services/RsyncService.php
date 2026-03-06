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
            '%s@%s:%s',
            $config['username'],
            $config['host'],
            $remotePath
        );

        $command = [
            'sshpass',
            '-p',
            $config['password'],
            'rsync',
            '-avz',
            '-e',
            sprintf('ssh -p %d -o StrictHostKeyChecking=no', $config['port']),
            $remoteSource,
            $localPath,
        ];

        Log::info('Starting remote rsync', [
            'command' => collect($command)->map(fn ($part, $key) => $key === 2 ? '******' : $part)->toArray(),
            'remote_path' => $remotePath,
            'local_path' => $localPath,
        ]);

        $result = Process::run($command);

        if ($result->failed()) {
            Log::error('Remote rsync failed', [
                'error' => $result->errorOutput(),
                'output' => $result->output(),
                'exit_code' => $result->exitCode(),
            ]);
            throw new RuntimeException('Remote rsync failed: '.$result->errorOutput());
        }

        Log::info('Remote rsync completed', ['local_path' => $localPath]);

        return $localPath;
    }
}
