<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeedRequest;
use App\Http\Resources\FeedPageResource;
use App\Services\FeedCache;
use App\Services\FeedService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class FeedController extends Controller
{
    public function __construct(
        private readonly FeedService $feedService,
        private readonly FeedCache $feedCache,
    ) {}

    public function index(FeedRequest $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->perPage();
        $shouldUseFirstPageCache = $request->shouldUseFirstPageCache();

        if ($shouldUseFirstPageCache) {
            $cachedFeedPage = $this->feedCache->firstPageFor($user);

            if ($cachedFeedPage) {
                return ApiResponse::ok($cachedFeedPage['data'], 'Feed fetched successfully.', $cachedFeedPage['meta']);
            }
        }

        $posts = $this->feedService->followedPosts($user, $perPage);
        $feedPageResource = new FeedPageResource($posts);
        $feedPage = $feedPageResource->toArray($request);

        if ($shouldUseFirstPageCache) {
            $this->feedCache->putFirstPage($user, $feedPage);
        }

        return ApiResponse::ok($feedPage['data'], 'Feed fetched successfully.', $feedPage['meta']);
    }
}
