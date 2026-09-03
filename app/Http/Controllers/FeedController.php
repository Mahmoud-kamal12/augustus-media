<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Services\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;

class FeedController extends Controller
{
    public function __construct(private FeedService $feed) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? FeedService::DEFAULT_PER_PAGE);
        $payload = $this->shouldUseFirstPageCache($request, $perPage)
            ? $this->feed->cachedFirstPage($request->user(), fn (): array => $this->payload($request, $perPage))
            : $this->payload($request, $perPage);

        return response()->json($payload);
    }

    private function shouldUseFirstPageCache(Request $request, int $perPage): bool
    {
        return $perPage === FeedService::DEFAULT_PER_PAGE
            && ! $request->query->has('cursor');
    }

    private function payload(Request $request, int $perPage): array
    {
        $paginator = $this->feed->followedPosts($request->user(), $perPage);

        return [
            'data' => PostResource::collection($paginator->getCollection())->resolve($request),
            'meta' => $this->meta($paginator),
        ];
    }

    private function meta(CursorPaginator $paginator): array
    {
        return [
            'per_page' => $paginator->perPage(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'previous_cursor' => $paginator->previousCursor()?->encode(),
            'has_more_pages' => $paginator->hasMorePages(),
        ];
    }
}
