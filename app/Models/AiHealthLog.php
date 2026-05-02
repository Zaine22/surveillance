<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiHealthLog extends Model
{
    use HasUuids;

    protected $table = 'ai_health_logs';

    protected $fillable = [
        'ai_model_id',
        'checked_at',
        'cpu_usage',
        'ram_usage',
        'gpu_usage',
        'metrics',
        'health_status',
        'message',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'metrics'    => 'array',
    ];

    public function aiModel()
    {
        return $this->belongsTo(AiModel::class);
    }
}
