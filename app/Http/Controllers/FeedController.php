<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeedRequest;
use App\Http\Resources\FeedPageResource;
use App\Services\FeedCache;
use App\Services\FeedService;
use Illuminate\Http\JsonResponse;

class FeedController extends Controller
{
    public function __construct(
        private FeedService $feed,
        private FeedCache $cache,
    ) {}

    public function index(FeedRequest $request): JsonResponse
    {
        $payload = $request->usesFirstPageCache()
            ? $this->cache->rememberFirstPage($request->user(), fn (): array => $this->payload($request))
            : $this->payload($request);

        return response()->json($payload);
    }

    private function payload(FeedRequest $request): array
    {
        return FeedPageResource::make(
            $this->feed->followedPosts($request->user(), $request->perPage())
        )->toArray($request);
    }
}
