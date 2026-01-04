<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DataSyncRecord extends Model
{
    use HasUuids;

    protected $table = 'data_sync_record';

    protected $fillable = [
        'source_path',
        'target_path',
        'file_name',
        'file_size',
        'checksum',
        'transfer_type',
        'status',
        'retry_count',
        'max_retry',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'retry_count' => 'integer',
        'max_retry' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
