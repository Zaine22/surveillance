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

    public $incrementing = false;
    protected $keyType   = 'string';

    protected static function booted(): void
    {
        static::updated(function (CrawlerTaskItem $item) {
            // Log::info('CrawlerTaskItem updated observer fired', [
            //     'item_id'        => $item->id,
            //     'status'         => $item->status,
            //     'status_changed' => $item->wasChanged('status'),
            //     'result_file'    => $item->result_file,
            // ]);

            if ($item->wasChanged('status') && $item->status === 'syncing' && ! empty($item->result_file)) {
                // Log::info('Dispatching SyncCrawlerFileJob based on status change', [
                //     'item_id' => $item->id,
                // ]);
                SyncCrawlerFileJob::dispatch($item);
            }

            if ($item->wasChanged('status') && $item->status === 'synced') {

                if (! empty($item->keywords)) {
                    try {
                        app(\App\Services\KeywordRankingService::class)
                            ->processHit($item->keywords);
                    } catch (\Throwable $e) {
                        Log::error('Keyword processHit failed', [
                            'item_id' => $item->id,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                }
                CrawlerTask::where('id', $item->task_id)
                    ->where('status', '!=', 'completed')
                    ->whereDoesntHave('items', function ($query) {
                        $query->where('status', '!=', 'synced');
                    })
                    ->update([
                        'status' => 'completed',
                    ]);

                if ($item->wasChanged('status') && $item->status === 'error') {

                    CrawlerTask::where('id', $item->task_id)
                        ->update([
                            'status' => 'error',
                        ]);
                }
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

    public function lexicon()
    {
        return $this->task->lexicon();
    }

    public function crawlerTask()
    {
        return $this->belongsTo(CrawlerTask::class, 'task_id');
    }
}
