<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $createdAt = $this->created_at ? $this->created_at->toISOString() : null;

        return [
            'id' => $this->id,
            'content' => $this->content,
            'author' => [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ],
            'likes_count' => $this->likes_count,
            'is_liked' => $this->is_liked,
            'created_at' => $createdAt,
        ];
    }
}
