<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiPredictResult extends Model
{
    use HasUuids;

    protected $table = 'ai_predict_results';

    protected $fillable = [
        'ai_model_task_id',
        'lexicon_id',
        'keywords',
        'ai_score',
        'analysis_result',
        'review_status',
        'reviewer',
        'review_date',
        'audit_status',
        'audit_user',
        'audit_date',
        'ai_analysis_result',
        'ai_analysis_detail',
        'ai_analysis_date',
    ];

    protected $casts = [
        'ai_score' => 'decimal:2',
        'ai_analysis_detail' => 'array',
        'review_date' => 'datetime',
        'audit_date' => 'datetime',
        'ai_analysis_date' => 'datetime',
    ];

    public function aiModelTask()
    {
        return $this->belongsTo(AiModelTask::class);
    }

}
