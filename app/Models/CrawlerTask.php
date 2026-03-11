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
    protected static function booted(): void
    {
        static::updated(function (CrawlerTaskItem $item) {

            if ($item->wasChanged('status') && $item->status === 'synced') {

                CrawlerTask::where('id', $item->task_id)
                    ->where('status', '!=', 'completed')
                    ->whereDoesntHave('items', function ($query) {
                        $query->where('status', '!=', 'synced');
                    })
                    ->update([
                        'status' => 'completed',
                    ]);
            }
        });
    }

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
