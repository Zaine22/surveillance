<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CaseManagementItem extends Model
{
    use HasUuids;

    protected $table = 'case_management_items';

    protected $fillable = [
        'case_management_id',
        'media_url',
        'crawler_page_url',
        'ai_result',
        'status',
        'reason',
        'other_reason',
        'ai_score',
        'keywords',
        'issue_date',
        'due_date',
    ];

    protected $casts = [
        'ai_score' => 'decimal:2',
    ];
}
