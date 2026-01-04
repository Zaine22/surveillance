<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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
}
