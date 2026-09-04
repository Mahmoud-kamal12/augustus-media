<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Jobs\NotifyFollowersOfNewPost;
use App\Models\Post;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function store(StorePostRequest $request): JsonResponse
    {
        $post = DB::transaction(function () use ($request) {
            $post = $request->user()->posts()->create($request->validated());

            NotifyFollowersOfNewPost::dispatch($post->id)->afterCommit();

            return $post;
        });

        $post->likes_count = 0;
        $post->is_liked = false;

        return ApiResponse::created(
            (new PostResource($post->load('author')))->resolve($request),
            'Post created successfully.'
        );
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        $viewerId = $request->user('sanctum')?->id ?? 0;

        $post
            ->load('author')
            ->loadCount('likedBy as likes_count')
            ->loadExists([
                'likedBy as is_liked' => function ($query) use ($viewerId): void {
                    $query->where('users.id', $viewerId);
                },
            ]);

        return ApiResponse::ok(
            (new PostResource($post))->resolve($request),
            'Post fetched successfully.'
        );
    }

    public function destroy(Post $post): JsonResponse
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return ApiResponse::deleted('Post deleted successfully.');
    }
}
