<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OperationLog extends Model
{
    use HasUuids;
    protected $fillable = [
        'user_id',
        'record_no',
        'operator_name',
        'operator_email',
        'department',
        'role',
        'page_url',
        'action',
        'status',
        'ip_address',
        'token',
        'cost_time',
        'page_data',
        'request_payload',
        'operation_time',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'page_data'       => 'array',
        'operation_time'  => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}