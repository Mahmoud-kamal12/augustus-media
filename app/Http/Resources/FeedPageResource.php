<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;

class FeedPageResource
{
    public function __construct(private readonly CursorPaginator $paginator) {}

    public function toArray(Request $request): array
    {
        return [
            'data' => PostResource::collection($this->paginator->getCollection())->resolve($request),
            'meta' => [
                'per_page' => $this->paginator->perPage(),
                'next_cursor' => $this->paginator->nextCursor()?->encode(),
                'previous_cursor' => $this->paginator->previousCursor()?->encode(),
                'has_more_pages' => $this->paginator->hasMorePages(),
            ],
        ];
    }
}
