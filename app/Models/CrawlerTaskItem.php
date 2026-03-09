<?php

namespace App\Models;

use App\Jobs\SyncCrawlerFileJob;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
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
            // Log for debugging
            Log::info('CrawlerTaskItem updated observer fired', [
                'item_id' => $item->id,
                'status' => $item->status,
                'status_changed' => $item->wasChanged('status'),
                'result_file' => $item->result_file,
            ]);

            // Trigger sync when status moves to 'syncing'
            if ($item->wasChanged('status') && $item->status === 'syncing' && ! empty($item->result_file)) {
                Log::info('Dispatching SyncCrawlerFileJob based on status change', [
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
