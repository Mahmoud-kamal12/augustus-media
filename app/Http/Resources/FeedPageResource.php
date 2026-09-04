<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;

class FeedPageResource
{
    public function __construct(private readonly CursorPaginator $paginator) {}

    public function toArray(Request $request): array
    {
        $posts = $this->paginator->getCollection();
        $postResources = PostResource::collection($posts);
        $data = $postResources->resolve($request);

        $nextCursor = $this->paginator->nextCursor();
        $previousCursor = $this->paginator->previousCursor();

        $meta = [
            'per_page' => $this->paginator->perPage(),
            'next_cursor' => $nextCursor ? $nextCursor->encode() : null,
            'previous_cursor' => $previousCursor ? $previousCursor->encode() : null,
            'has_more_pages' => $this->paginator->hasMorePages(),
        ];

        return [
            'data' => $data,
            'meta' => $meta,
        ];
    }
}
