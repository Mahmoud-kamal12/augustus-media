<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    use HasFactory;

    public const TABLE = 'posts';

    protected $table = self::TABLE;

    protected $fillable = [
        'user_id',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'likes_count' => 'integer',
            'is_liked' => 'boolean',
        ];
    }

    public function scopeWithLikeSummaryFor(Builder $query, int $userId): Builder
    {
        $postTable = self::TABLE;
        $likeTable = Like::TABLE;
        $isLikedSql = "exists (select 1 from {$likeTable} where {$likeTable}.post_id = {$postTable}.id and {$likeTable}.user_id = ?) as is_liked";

        return $query
            ->withCount('likedBy as likes_count')
            ->selectRaw($isLikedSql, [$userId]);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function likedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, Like::TABLE)
            ->withPivot('created_at');
    }
}
