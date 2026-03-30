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
        $model = AiModel::find($id);

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
}
