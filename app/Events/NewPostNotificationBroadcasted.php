<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewPostNotificationBroadcasted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly array $notification,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("users.{$this->userId}.notifications"),
        ];
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
