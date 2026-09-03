<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Jobs\NotifyFollowersOfNewPost;
use App\Models\Post;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function __construct(private LikeService $likes) {}

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

    public function show(Post $post): PostResource
    {
        $post->load('author');
        $post->likes_count = $this->likes->countsFor([$post->id])[$post->id];

        $user = Auth::guard('sanctum')->user();
        $post->is_liked = $user
            ? in_array($post->id, $this->likes->likedPostIds($user, [$post->id]), true)
            : false;

        return new PostResource($post);
    }

    public function destroy(Post $post): Response
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return response()->noContent();
    }
}
