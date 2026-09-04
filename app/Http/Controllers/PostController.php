<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Jobs\NotifyFollowersOfNewPost;
use App\Models\Post;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function store(StorePostRequest $request): JsonResponse
    {
        $user = $request->user();
        $validatedData = $request->validated();
        $post = $user->posts()->create($validatedData);

        NotifyFollowersOfNewPost::dispatch($post->id);

        $post->likes_count = 0;
        $post->is_liked = false;
        $post->load('author');

        $postResource = new PostResource($post);
        $responseData = $postResource->resolve($request);

        return ApiResponse::created($responseData, 'Post created successfully.');
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $viewerId = $viewer ? $viewer->id : 0;

        $postQuery = Post::query()
            ->whereKey($post->id)
            ->with('author:id,name');

        $postQuery = Post::addLikesCountAndViewerState($postQuery, $viewerId);
        $post = $postQuery->firstOrFail();
        $postResource = new PostResource($post);
        $responseData = $postResource->resolve($request);

        return ApiResponse::ok($responseData, 'Post fetched successfully.');
    }

    public function destroy(Post $post): JsonResponse
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return ApiResponse::deleted('Post deleted successfully.');
    }
}
