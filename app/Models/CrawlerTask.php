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

    public function crawlerConfig()
    {
        return $this->belongsTo(CrawlerConfig::class, 'crawler_config_id');
    }

    public function lexicon()
    {
        return $this->belongsTo(Lexicon::class, 'lexicon_id');
    }
    public function items()
    {
        return $this->hasMany(CrawlerTaskItem::class, 'task_id');
    }
}