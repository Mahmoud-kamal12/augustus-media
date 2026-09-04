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

        if ($request->shouldUseFirstPageCache()) {
            $cachedFeedPage = $this->feedCache->firstPageFor($user);

            if ($cachedFeedPage) {
                return ApiResponse::ok($cachedFeedPage['data'], 'Feed fetched successfully.', $cachedFeedPage['meta']);
            }
        }

        $feedPage = (new FeedPageResource(
            $this->feedService->followedPosts($user, $request->perPage())
        ))->toArray($request);

        if ($request->shouldUseFirstPageCache()) {
            $this->feedCache->putFirstPage($user, $feedPage);
        }

        return ApiResponse::ok($feedPage['data'], 'Feed fetched successfully.', $feedPage['meta']);
    }
}
