<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Jobs\NotifyFollowersOfNewPost;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
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

        return (new PostResource($post->load('author')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Post $post): PostResource
    {
        return new PostResource($post->load('author'));
    }

    public function destroy(Post $post): Response
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return response()->noContent();
    }
}
