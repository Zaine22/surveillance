<?php

namespace App\Services;

class RsyncService
{
    public function __construct() {}

    public function sync(string $source, string $target): string
    {
        return $target; // wrap real rsync here
    }
}
