<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SystemData extends Model
{
    use HasUuids;

    protected $table = 'system_data';

    protected $fillable = [
        'data_code',
        'data_type',
        'data_value',
        'data_name',
        'sort',
        'status',
    ];

    protected $casts = [
        'sort' => 'integer',
    ];
}
