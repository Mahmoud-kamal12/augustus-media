<?php

namespace App\Http\Controllers;

use App\Services\UserFeedService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function __construct(
        private readonly UserFeedService $userFeedService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $feedPage = $this->userFeedService->getFeedPageForUser($user, $request);

        $feedPosts = $feedPage['data'];
        $feedMeta = $feedPage['meta'];

        return ApiResponse::ok($feedPosts, 'Feed fetched successfully.', $feedMeta);
    }
}
