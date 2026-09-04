<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $createdAt = $this->created_at ? $this->created_at->toISOString() : null;
        $likesCount = (int) $this->likes_count;
        $isLiked = (bool) $this->is_liked;

        return [
            'id' => $this->id,
            'content' => $this->content,
            'author' => [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ],
            'likes_count' => $likesCount,
            'is_liked' => $isLiked,
            'created_at' => $createdAt,
        ];
    }
}
