<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeedRequest;
use App\Services\UserFeedService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class FeedController extends Controller
{
    public function __construct(
        private readonly UserFeedService $userFeedService,
    ) {}

    public function index(FeedRequest $request): JsonResponse
    {
        $user = $request->user();
        $feedPagination = config('feed.pagination');
        $feedFilters = $request->validated();
        $postsPerPage = (int) ($feedFilters['per_page'] ?? $feedPagination['default_per_page']);
        $useFirstPageCache = ! $request->query->has('cursor')
            && ! $request->query->has('per_page');

        $feedPage = $this->userFeedService->getFeedPage(
            user: $user,
            postsPerPage: $postsPerPage,
            useFirstPageCache: $useFirstPageCache,
            request: $request,
        );

        $feedPosts = $feedPage['data'];
        $feedMeta = $feedPage['meta'];

        return ApiResponse::ok($feedPosts, 'Feed fetched successfully.', $feedMeta);
    }
}
