<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseFeedback extends Model
{
    protected $table = 'case_feedback';

    protected $fillable = [
        'case_id',
        'url',
        'is_illegal',
        'legal_basis',
        'reason',
    ];

    protected $casts = [
        'is_illegal' => 'boolean',
    ];
}
