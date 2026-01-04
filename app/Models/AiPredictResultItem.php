<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiPredictResultItem extends Model
{
    use HasUuids;

    protected $table = 'ai_predict_result_items';

    protected $fillable = [
        'ai_predict_result_id',
        'media_url',
        'crawler_page_url',
        'ai_result',
        'status',
        'reason',
        'other_reason',
        'ai_score',
        'keywords',
    ];

    protected $casts = [
        'ai_score' => 'decimal:2',
    ];
}
