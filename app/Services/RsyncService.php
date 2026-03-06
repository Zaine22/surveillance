<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;

class RsyncService
{
    public function __construct() {}

    public function sync(string $source, string $target): string
    {
        $result = Process::run(['rsync', '-avz', $source, $target]);

        if ($result->failed()) {
            throw new RuntimeException('Rsync failed: '.$result->errorOutput());
        }

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

        $result = Process::run($command);

        if ($result->failed()) {
            throw new RuntimeException('Remote rsync failed: '.$result->errorOutput());
        }

        return $localPath;
    }
}
