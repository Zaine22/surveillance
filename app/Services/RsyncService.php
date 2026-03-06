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
}
