<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CrawlerConfig extends Model
{
    use HasUuids;

    protected $table = 'crawler_configs';

    protected $fillable = [
        'name',
        'sources',
        'lexicon_id',
        'description',
        'frequency_code',
        'status',
    ];

    protected $casts = [
        'sources' => 'array',
    ];

    public function lexicon()
    {
        return $this->belongsTo(Lexicon::class);
    }
    public function tasks()
    {
        return $this->hasMany(CrawlerTask::class);
    }
}
