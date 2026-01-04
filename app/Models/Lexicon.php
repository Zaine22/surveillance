<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Lexicon extends Model
{
    use HasUuids;

    protected $table = 'lexicon';

    protected $fillable = [
        'name',
        'remark',
        'status',
    ];
}
