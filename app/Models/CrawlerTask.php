<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CrawlerTask extends Model
{
    use HasUuids;

    protected $table = 'crawler_tasks';

    protected $fillable = [
        'crawler_config_id',
        'lexicon_id',
        'status',
    ];
}
