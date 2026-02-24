<?php
namespace App\Services;

use App\Models\BotMachine;

class BotMachineService
{
    public function getAvailableBot(): ?BotMachine
    {
        return BotMachine::where('status', 'enabled')
            ->whereIn('health_status', ['normal', 'stable', 'slightly_busy'])
            ->orderBy('health_checked_at', 'asc')
            ->first();
    }
}
