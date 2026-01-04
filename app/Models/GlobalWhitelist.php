<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class GlobalWhitelist extends Model
{
    use HasUuids;

    protected $table = 'global_whitelist';

    protected $fillable = [
        'url',
    ];
}
