<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LikeService
{
    public function like(User $user, Post $post): bool
    {
        return DB::table('likes')->insertOrIgnore([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]) === 1;
    }

    public function unlike(User $user, Post $post): bool
    {
        return DB::table('likes')
            ->where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->delete() > 0;
    }

    public function summariesForPosts(User $viewer, array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        $rows = DB::table('likes')
            ->whereIn('post_id', $postIds)
            ->selectRaw('post_id, COUNT(*) as likes_count, MAX(user_id = ?) as is_liked', [$viewer->id])
            ->groupBy('post_id')
            ->get();

        $summariesByPostId = [];

        foreach ($rows as $row) {
            $summariesByPostId[$row->post_id] = [
                'likes_count' => (int) $row->likes_count,
                'is_liked' => (bool) $row->is_liked,
            ];
        }

        return $summariesByPostId;
    }

    public function summaryForPost(Post $post, ?User $viewer): array
    {
        $viewerId = $viewer?->id ?? 0;
        $row = DB::table('likes')
            ->where('post_id', $post->id)
            ->selectRaw('COUNT(*) as likes_count, MAX(user_id = ?) as is_liked', [$viewerId])
            ->first();

        return [
            'likes_count' => (int) $row->likes_count,
            'is_liked' => (bool) $row->is_liked,
        ];
    }
}
