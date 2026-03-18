<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SystemNotice extends Model
{
    use HasUuids;

    protected $table = 'system_notices';

    protected $fillable = [
        'status',
        'publish_date',
        'expire_at',
        'title',
        'content',
        'created_by',
    ];

    protected $casts = [
        'publish_date' => 'datetime',
        'expire_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
