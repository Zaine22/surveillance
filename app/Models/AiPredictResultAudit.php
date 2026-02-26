<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiPredictResultAudit extends Model
{
    use HasUuids;

    protected $fillable = [
        'ai_predict_result_id',
        'auditor_id',
        'decision',
        'valid_count',
        'invalid_count',
        'summary',
    ];

    public function result()
    {
        return $this->belongsTo(AiPredictResult::class,'ai_predict_result_id');
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }
}
