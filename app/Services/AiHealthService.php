<?php
namespace App\Services;

use App\Models\AiModel;
use Illuminate\Support\Facades\Http;    

class AiHealthService
{
    public function check(AiModel $model): void
    {
        $response = Http::timeout(5)->get(config('services.ai.metrics_url'));

        if (! $response->successful()) {
            return;
        }

        $data = $response->json();

        $cpu = $data['cpu']['usage_percent'] ?? 0;
        $ram = $data['ram']['usage_percent'] ?? 0;
        $gpu = $data['gpu'][0]['gpu_usage_percent'] ?? 0;

        $status = $this->mapHealth($cpu, $ram, $gpu);

        $model->update([
            'health_checked_at' => now(),
            'health_status'     => $status,
            'content'           => $data,
        ]);
    }

    protected function mapHealth($cpu, $ram, $gpu): string
    {
        if ($cpu > 85 || $ram > 85 || $gpu > 85) {
            return 'busy';
        }

        if ($cpu > 65 || $ram > 65 || $gpu > 65) {
            return 'slightly_busy';
        }

        if ($cpu < 40 && $ram < 40 && $gpu < 40) {return 'normal';}return 'stable';
    }
}
