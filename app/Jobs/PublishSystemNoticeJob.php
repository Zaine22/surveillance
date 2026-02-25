<?php

namespace App\Jobs;

use App\Models\SystemNotice;
use App\Events\SystemNoticeEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishSystemNoticeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $noticeId) {}

    public function handle(): void
    {
        $notice = SystemNotice::find($this->noticeId);

        // Safety check
        if (
            ! $notice ||
            $notice->status !== 'scheduled' ||
            $notice->publish_date->isFuture()
        ) {
            return;
        }

        $notice->update([
            'status' => 'published'
        ]);

        broadcast(new SystemNoticeEvent($notice));
    }
}
