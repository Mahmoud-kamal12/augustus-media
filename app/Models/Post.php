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
        return $query
            ->withCount('likedBy as likes_count')
            ->selectRaw(
                'exists (select 1 from likes where likes.post_id = posts.id and likes.user_id = ?) as is_liked',
                [$userId]
            );
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function likedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'likes')
            ->withPivot('created_at');
    }
}
