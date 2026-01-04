<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NotifyTemplate extends Model
{
    use HasUuids;

    protected $table = 'notify_template';

    protected $fillable = [
        'type',
        'name',
        'content',
    ];
}
