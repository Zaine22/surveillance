<?php

namespace App\Jobs;

use App\Models\SystemNotice;
use App\Events\SystemNoticeEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireSystemNoticeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $noticeId) {}

    public function handle(): void
    {
        $notice = SystemNotice::find($this->noticeId);

        if (
            ! $notice ||
            $notice->status !== 'published' ||
            $notice->expire_at->isFuture()
        ) {
            return;
        }

        $notice->update([
            'status' => 'expired'
        ]);

        broadcast(new SystemNoticeEvent($notice));
    }
}
