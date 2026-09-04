<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;

class FeedPageResource
{
    public function __construct(private readonly CursorPaginator $postPaginator) {}

    public function toArray(Request $request): array
    {
        $posts = $this->postPaginator->getCollection();
        $postResources = PostResource::collection($posts);
        $postsResponseData = $postResources->toArray($request);

        $nextCursor = $this->postPaginator->nextCursor();
        $previousCursor = $this->postPaginator->previousCursor();

        $meta = [
            'per_page' => $this->postPaginator->perPage(),
            'next_cursor' => $nextCursor ? $nextCursor->encode() : null,
            'previous_cursor' => $previousCursor ? $previousCursor->encode() : null,
            'has_more_pages' => $this->postPaginator->hasMorePages(),
        ];

        return [
            'data' => $postsResponseData,
            'meta' => $meta,
        ];
    }
}
