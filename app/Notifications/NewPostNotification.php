<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewPostNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Post $post) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return self::databaseData($this->post);
    }

    public static function databaseData(Post $post): array
    {
        return [
            'post_id' => $post->id,
            'author_id' => $post->user_id,
            'author_name' => $post->author?->name,
            'content_preview' => Str::limit($post->content, 120),
            'created_at' => $post->created_at?->toISOString(),
        ];
    }
}
