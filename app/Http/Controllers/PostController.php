<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Jobs\NotifyFollowersOfNewPost;
use App\Models\Post;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function __construct(private readonly LikeService $likeService) {}

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = DB::transaction(function () use ($request) {
            $post = $request->user()->posts()->create($request->validated());

            NotifyFollowersOfNewPost::dispatch($post->id)->afterCommit();

            return $post;
        });

        $post->likes_count = 0;
        $post->is_liked = false;

        return (new PostResource($post->load('author')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Post $post): PostResource
    {
        $post->load('author');
        $post->likes_count = $this->likeService->countFor($post);

        $user = $request->user('sanctum');
        $post->is_liked = $user ? $this->likeService->isLikedBy($post, $user) : false;

        return new PostResource($post);
    }

    public function destroy(Post $post): Response
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return response()->noContent();
    }
}
