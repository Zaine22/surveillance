<?php
namespace App\Jobs;

use App\Events\SystemNoticeEvent;
use App\Models\SystemNotice;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireSystemNoticeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $noticeId)
    {}

    // public function handle(): void
    // {
    //     $notice = SystemNotice::find($this->noticeId);

    //     if (
    //         ! $notice ||
    //         $notice->status !== 'published' ||
    //         $notice->expire_at->isFuture()
    //     ) {
    //         return;
    //     }

    //     $notice->update([
    //         'status' => 'expired'
    //     ]);

    //     broadcast(new SystemNoticeEvent($notice));
    // }

    public function handle(): void
    {
        $notice = SystemNotice::find($this->noticeId);

        if (! $notice) {
            return;
        }

        if (empty($notice->expire_at)) {
            return;
        }

        // Already removed
        if ($notice->status === 'removed') {
            return;
        }

        // Only pending or published notices can be removed by expire job
        if (! in_array($notice->status, ['pending', 'published'], true)) {
            return;
        }

        $expireAt = Carbon::parse($notice->expire_at);

        // If expire date is still future, do nothing
        if ($expireAt->isFuture()) {
            return;
        }

        $notice->update([
            'status' => 'removed',
        ]);

        broadcast(new SystemNoticeEvent($notice->fresh()));
    }
}
