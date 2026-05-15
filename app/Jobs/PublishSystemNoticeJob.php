<?php
namespace App\Jobs;

use App\Events\SystemNoticeEvent;
use App\Models\SystemNotice;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishSystemNoticeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $noticeId)
    {}

    // public function handle(): void
    // {
    //     $notice = SystemNotice::find($this->noticeId);

    //     // Safety check
    //     if (
    //         ! $notice ||
    //         $notice->status !== 'pending' ||
    //         $notice->publish_date->isFuture()
    //     ) {
    //         return;
    //     }

    //     $notice->update([
    //         'status' => 'published',
    //     ]);

    //     broadcast(new SystemNoticeEvent($notice));
    // }

    public function handle(): void
    {
        $notice = SystemNotice::find($this->noticeId);

        if (! $notice) {
            return;
        }

        // Only pending notice can be published
        if ($notice->status !== 'pending') {
            return;
        }

        if (empty($notice->publish_date)) {
            return;
        }

        $now = Carbon::now();

        $publishAt = Carbon::parse($notice->publish_date);

        $expireAt = ! empty($notice->expire_at)
            ? Carbon::parse($notice->expire_at)
            : null;

        // If publish date is still future, do nothing
        if ($publishAt->isFuture()) {
            return;
        }

        // If notice is already expired, change to removed, not published
        if ($expireAt && $expireAt->lessThanOrEqualTo($now)) {
            $notice->update([
                'status' => 'removed',
            ]);

            broadcast(new SystemNoticeEvent($notice->fresh()));

            return;
        }

        $notice->update([
            'status' => 'published',
        ]);

        broadcast(new SystemNoticeEvent($notice->fresh()));
    }
}
