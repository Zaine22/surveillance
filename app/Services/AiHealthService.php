<?php
namespace App\Services;

use App\Models\AiHealthLog;
use App\Models\AiModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiHealthService
{
    public function check(AiModel $model): void
    {
        try {
            $response = Http::timeout(5)
                ->retry(3, 200)
                ->get(config('services.ai.metrics_url'));

            if (! $response->successful()) {
                Log::warning('AI metrics request failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return;
            }

            $data = $response->json();

            $cpu = data_get($data, 'cpu.usage_percent');
            $ram = data_get($data, 'ram.usage_percent');

            $gpuUsage = null;
            $latency  = data_get($data, 'latency');

            // ✅ detect GPU
            if (isset($data['gpu'][0]['gpu_usage_percent'])) {
                $gpuUsage = $data['gpu'][0]['gpu_usage_percent'];
            }

            // ✅ detect GPU error
            $gpuError = data_get($data, 'gpu.error');

            // ✅ determine status FIRST
            $status = $this->mapHealth($cpu, $ram, $gpuUsage, $gpuError);

            // ✅ build message AFTER status
            if ($gpuError) {
                $message = "GPU 異常（無法取得顯示卡資訊）";
            } else {
                $message = "定時向主機確認健康度："
                    . ($latency ? "延遲 {$latency}ms、" : "")
                    . "CPU {$cpu}%、記憶體 {$ram}%"
                    . ($gpuUsage !== null ? "、GPU {$gpuUsage}%" : "");
            }

            // ✅ update model
            $model->update([
                'health_checked_at' => now(),
                'health_status'     => $status,
                'content'           => $data,
            ]);

            // ✅ log
            AiHealthLog::create([
                'id'            => (string) Str::uuid(),
                'ai_model_id'   => $model->id,
                'checked_at'    => now(),
                'cpu_usage'     => $cpu,
                'ram_usage'     => $ram,
                'gpu_usage'     => $gpuUsage,
                'metrics'       => $data,
                'health_status' => $status,
                'message'       => $message,
            ]);

        } catch (\Throwable $e) {
            Log::error('AI health check failed', [
                'model_id' => $model->id,
                'error'    => $e->getMessage(),
            ]);

            $model->update([
                'health_status' => 'error',
            ]);
        }
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

    protected function mapHealth($cpu, $ram, $gpu, $gpuError = null): string
    {
        if ($gpuError) {
            return 'error';
        }

        $gpu = $gpu ?? 0;

        if ($cpu > 85 || $ram > 85 || $gpu > 85) {
            return 'busy';
        }

        if ($cpu > 65 || $ram > 65 || $gpu > 65) {
            return 'slightly_busy';
        }

        return 'stable';
    }

    private function calculateStatus(int $latency, int $cpu, int $memory): string
    {
        if ($cpu > 80 || $memory > 85 || $latency > 100) {
            return '非常擁擠';
        }

        if ($cpu > 60 || $memory > 70 || $latency > 50) {
            return '輕微壅塞';
        }

        return '穩定';
    }
    public function getAiHealth(): array
    {

        $model = AiModel::first();
        $this->check($model);
        return AiHealthLog::orderBy('checked_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'checked_at'    => $item->checked_at->format('Y-m-d H:i'),
                    'message'       => $item->message,
                    'health_status' => $item->health_status,
                ];
            })
            ->toArray();
    }
    $rand = rand(1, 100);

    if ($rand <= 60) {
        return 'stable';
    }

    if ($rand <= 85) {
        return 'slightly_busy';
    }

    return 'busy';
}