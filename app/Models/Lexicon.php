<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Lexicon extends Model
{
    use HasUuids;

    protected $table = 'lexicons';

    protected $fillable = [
        'name',
        'remark',
        'status',
        'keywords',
    ];

    public function keywords()
    {
        return $this->hasMany(LexiconKeyword::class, 'lexicon_id');
    }
}
