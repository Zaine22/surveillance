<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OperationLog extends Model
{
    use HasUuids;
    protected $fillable = [
        'user_id',
        'operator_name',
        'department',
        'page_url',
        'action',
        'status',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
