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
        $buildFeedPage = function () use ($request): array {
            return (new FeedPageResource(
                $this->feedService->followedPosts($request->user(), $request->perPage())
            ))->toArray($request);
        };

        if ($request->shouldUseFirstPageCache()) {
            $responseBody = $this->feedCache->rememberFirstPage($request->user(), $buildFeedPage);

            return ApiResponse::ok($responseBody['data'], 'Feed fetched successfully.', $responseBody['meta']);
        }

        $responseBody = $buildFeedPage();

        return ApiResponse::ok($responseBody['data'], 'Feed fetched successfully.', $responseBody['meta']);
    }
}
