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
                $cachedFeedPosts = $cachedFeedPage['data'];
                $cachedFeedMeta = $cachedFeedPage['meta'];

                return ApiResponse::ok($cachedFeedPosts, 'Feed fetched successfully.', $cachedFeedMeta);
            }
        }

        $postPaginator = $this->feedService->followedPostsPage($user, $perPage);
        $feedPageResource = new FeedPageResource($postPaginator);
        $feedPage = $feedPageResource->toArray($request);

        if ($shouldUseFirstPageCache) {
            $this->feedCache->putFirstPage($user, $feedPage);
        }

        $feedPosts = $feedPage['data'];
        $feedMeta = $feedPage['meta'];

        return ApiResponse::ok($feedPosts, 'Feed fetched successfully.', $feedMeta);
    }
}
