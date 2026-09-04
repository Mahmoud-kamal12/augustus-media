<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Like extends Model
{
    public const TABLE = 'likes';

    public $incrementing = false;

    public $timestamps = false;

    protected $table = self::TABLE;

    protected $fillable = [
        'post_id',
        'user_id',
        'created_at',
    ];

    // The likes table uses post_id and user_id together as its primary key.
    protected function setKeysForSaveQuery($query)
    {
        $postId = $this->getOriginal('post_id');
        $userId = $this->getOriginal('user_id');

        return $query
            ->where('post_id', $postId)
            ->where('user_id', $userId);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
