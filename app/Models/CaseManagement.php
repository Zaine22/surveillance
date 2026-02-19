<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CaseManagement extends Model
{
    use HasUuids;

    protected $table = 'case_management';

    protected $fillable = [
        'internal_case_no',
        'external_case_no',
        'ai_predict_result_id',
        'keywords',
        'status',
        'comment',
    ];

    public function aiPredictResult()
{
    return $this->belongsTo(AiPredictResult::class);
}
public function aiModelTask()
{
    return $this->belongsTo(AiModelTask::class);
}


}
