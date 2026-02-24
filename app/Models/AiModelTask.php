<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiModelTask extends Model
{
    use HasUuids;

    protected $table    = 'ai_model_tasks';
    protected $fillable = [
        'ai_model_id',
        'crawler_task_item_id',
        'file_name',
        'status',
    ];

    public function predictResults()
    {
        return $this->hasMany(AiPredictResult::class);
    }

    public function crawlerTaskItem()
    {
        return $this->belongsTo(CrawlerTaskItem::class);
    }
}