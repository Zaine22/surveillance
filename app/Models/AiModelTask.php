<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiModelTask extends Model
{
    use HasUuids;

    protected $fillable = [
        'ai_model_id',
        'crawler_task_item_id',
        'file_name',
        'status',
    ];
}
