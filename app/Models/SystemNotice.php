<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SystemNotice extends Model
{
    use HasUuids;

    protected $table = 'system_notice';

    protected $fillable = [
        'status',
        'publish_date',
        'expire_at',
        'title',
        'content',
    ];

    protected $casts = [
        'publish_date' => 'datetime',
        'expire_at' => 'datetime',
    ];
}
