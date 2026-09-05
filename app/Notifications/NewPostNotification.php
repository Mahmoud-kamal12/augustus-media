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
        return self::payloadForPost($this->post);
    }

    public static function payloadForPost(Post $post): array
    {
        $authorName = $post->author ? $post->author->name : null;
        $contentPreview = Str::limit($post->content, 120);
        $createdAt = $post->created_at ? $post->created_at->toISOString() : null;

        return [
            'post_id' => $post->id,
            'author_id' => $post->user_id,
            'author_name' => $authorName,
            'content_preview' => $contentPreview,
            'created_at' => $createdAt,
        ];
    }
}
