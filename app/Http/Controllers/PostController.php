<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(
        private readonly PostService $postService,
    ) {}

    public function store(StorePostRequest $request): JsonResponse
    {
        $user = $request->user();
        $validatedData = $request->validated();
        $post = $this->postService->createPost($user, $validatedData);

        $postResource = new PostResource($post);
        $createdPostData = $postResource->toArray($request);

        return ApiResponse::created($createdPostData, 'Post created successfully.');
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        $viewer = $request->user();
        $post = $this->postService->getPostDetails($post, $viewer);

        $postResource = new PostResource($post);
        $postData = $postResource->toArray($request);

        return ApiResponse::ok($postData, 'Post fetched successfully.');
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->postService->deletePost($post);

        return ApiResponse::deleted('Post deleted successfully.');
    }
}
