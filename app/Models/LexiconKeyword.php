<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LexiconKeyword extends Model
{
    use HasUuids;

    protected $table = 'lexicon_keywords';

    protected $fillable = [
        'lexicon_id',
        'keywords',
        'parent_id',
        'language',
        'crawl_hit_count',
        'case_count',
        'status',
    ];

    protected $casts = [
        'crawl_hit_count' => 'integer',
        'case_count'      => 'integer',
        'keywords'        => 'array',
    ];

    public function lexicon()
    {
        return $this->belongsTo(Lexicon::class, 'lexicon_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(LexiconKeyword::class, 'parent_id');
    }
}
