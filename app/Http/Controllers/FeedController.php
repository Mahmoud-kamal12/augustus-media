<?php

namespace App\Http\Controllers;

use App\Http\Resources\FeedPageResource;
use App\Services\FeedCache;
use App\Services\FeedService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function __construct(
        private readonly FeedService $feedService,
        private readonly FeedCache $feedCache,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $feedPagination = config('feed.pagination');
        $defaultPostsPerPage = (int) $feedPagination['default_per_page'];
        $maximumPostsPerPage = (int) $feedPagination['max_per_page'];

        $validatedData = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', "max:{$maximumPostsPerPage}"],
        ]);

        $perPage = (int) ($validatedData['per_page'] ?? $defaultPostsPerPage);
        $shouldUseFirstPageCache = ! $request->query->has('cursor')
            && ! $request->query->has('per_page');

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
