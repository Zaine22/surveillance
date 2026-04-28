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

    public function getModels(array $filters = [])
    {
        $query = AiModel::query();

        if (! empty($filters['health_status'])) {
            $query->where('health_status', $filters['health_status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->latest()->get()->map(fn($m) => $this->format($m));
    }

    public function getModelById(string $id)
    {
        $model = AiModel::findOrFail($id);

        return $model ? $this->format($model) : null;
    }

    public function getStats(): array
    {
        return [
            'total'         => AiModel::count(),
            'busy'          => AiModel::where('health_status', 'busy')->count(),
            'slightly_busy' => AiModel::where('health_status', 'slightly_busy')->count(),
            'stable'        => AiModel::where('health_status', 'stable')->count(),
            'normal'        => AiModel::where('health_status', 'normal')->count(),
        ];
    }

    protected function format(AiModel $model): array
    {
        $content = $model->content ?? [];

        return [
            'id'          => $model->id,
            'name'        => $model->name,
            'type'        => $model->type,
            'version'     => $model->version,
            'description' => $model->description,
            'status'      => $model->status,

            'health'      => [
                'status'     => $model->health_status,
                'checked_at' => $model->health_checked_at,
            ],

            'metrics'     => [
                'cpu' => data_get($content, 'cpu.usage_percent', 0),
                'ram' => data_get($content, 'ram.usage_percent', 0),
                'gpu' => data_get($content, 'gpu.0.gpu_usage_percent', 0),
            ],
        ];
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

    public function getAiHealth(): array
    {
        return [
            $this->randomItem(now()),
            $this->randomItem(now()->subMinutes(rand(10, 120))),
            $this->randomItem(now()->subMinutes(rand(120, 300))),
        ];
    }
    private function randomItem($time): array
    {
        $latency = rand(20, 60);
        $cpu     = rand(20, 60);
        $memory  = rand(30, 70);

        return $this->formatItem($time, $latency, $cpu, $memory);
    }


    public function sync(): array
    {
        $latency = rand(20, 150);
        $cpu     = rand(10, 95);
        $memory  = rand(20, 95);

        return $this->formatItem(now(), $latency, $cpu, $memory);
    }

    private function formatItem($time, $latency, $cpu, $memory): array
    {
        $status = $this->calculateStatus($latency, $cpu, $memory);

        return [
            'checked_at' => $time->format('Y-m-d H:i'),
            'message'    => "定時向主機確認健康度：延遲 {$latency}ms、CPU {$cpu}%、記憶體 {$memory}%",
            'health_status' => $status,
        ];
    }

    private function calculateStatus($latency, $cpu, $memory): string
    {
        if ($cpu > 80 || $memory > 85 || $latency > 100) {
            return '非常擁擠';
        }

        if ($cpu > 60 || $memory > 70 || $latency > 50) {
            return '輕微壅塞';
        }

        return '穩定';
    }
}
