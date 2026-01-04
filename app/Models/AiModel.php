<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    use HasUuids;

    protected $table = 'ai_models';

    protected $fillable = [
        'name',
        'type',
        'version',
        'description',
        'health_checked_at',
        'content',
        'health_status',
        'status',
    ];

    protected $casts = [
        'health_checked_at' => 'datetime',
        'content' => 'array',
    ];
}
