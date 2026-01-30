<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ValidationRecord extends Model
{
    use HasUuids;

    protected $table = 'validation_records';

    protected $fillable = [
        'send_type',
        'send_to',
        'validate_type',
        'validate_code',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];
}
