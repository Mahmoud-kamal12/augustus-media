<?php

namespace App\Services;

use App\Jobs\NotifyFollowersOfNewPost;
use App\Models\Post;
use App\Models\User;

class PostService
{
    public function createPost(User $user, array $postData): Post
    {
        $post = $user->posts()->create($postData);

        NotifyFollowersOfNewPost::dispatch($post->id)->afterCommit();

        $post->likes_count = 0;
        $post->is_liked = false;
        $post->load('author:id,name');

        return $post;
    }

    public function getPostDetails(Post $post, ?User $viewer): Post
    {
        $post->load('author:id,name');
        $post->is_liked = $post->isLikedBy($viewer);

        return $post;
    }

    public function deletePost(Post $post): void
    {
        $post->delete();
    }
}
