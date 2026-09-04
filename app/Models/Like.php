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

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
