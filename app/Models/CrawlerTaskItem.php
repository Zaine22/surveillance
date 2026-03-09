<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Jobs\SyncCrawlerFileJob;
use Illuminate\Support\Facades\Log;

class CrawlerTaskItem extends Model
{
    use HasUuids;

    protected $table = 'crawler_task_items';

    protected $fillable = [
        'task_id',
        'keywords',
        'crawler_machine',
        'result_file',
        'crawl_location',
        'status',
        'error_message',
    ];

    protected static function booted(): void
    {
        static::updated(function (CrawlerTaskItem $item) {
            Log::info('CrawlerTaskItem updated observer fired', [
                'item_id' => $item->id,
                'status' => $item->status,
                'result_file_changed' => $item->wasChanged('result_file'),
                'result_file_value' => $item->result_file,
            ]);

            if ($item->wasChanged('result_file') && ! empty($item->result_file)) {
                Log::info('Dispatching SyncCrawlerFileJob', [
                    'item_id' => $item->id,
                ]);
                SyncCrawlerFileJob::dispatch($item);
            }
        });
    }

    public function task()
    {
        return $this->belongsTo(CrawlerTask::class, 'task_id');
    }

    public function crawlerConfig()
    {
        return $this->task->crawlerConfig();
    }
}
