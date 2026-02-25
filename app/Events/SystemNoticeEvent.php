<?php

namespace App\Events;

use App\Models\SystemNotice;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemNoticeEvent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public SystemNotice $systemNotice) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('system-notices'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notice.published';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->systemNotice->id,
            'title' => $this->systemNotice->title,
            'content' => $this->systemNotice->content,
            'status' => $this->systemNotice->status,
            'publish_date' => $this->systemNotice->publish_date,
            'expire_at' => $this->systemNotice->expire_at,
        ];
    }
}
