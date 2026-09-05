<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewPostNotificationBroadcasted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly array $userIds,
        public readonly array $notification,
    ) {}

    public function broadcastOn(): array
    {
        return array_map(
            fn (int $userId): PrivateChannel => new PrivateChannel("users.{$userId}.notifications"),
            $this->userIds,
        );
    }

    public function broadcastAs(): string
    {
        return 'new-post-notification';
    }

    public function broadcastWith(): array
    {
        return $this->notification;
    }
}
