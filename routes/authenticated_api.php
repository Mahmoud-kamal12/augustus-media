<?php

use App\Http\Controllers\FeedController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/feed', [FeedController::class, 'index']);

Route::post('/follow/{userToFollow}', [FollowController::class, 'store']);
Route::delete('/follow/{userToUnfollow}', [FollowController::class, 'destroy']);

Route::post('/posts', [PostController::class, 'store']);
Route::delete('/posts/{post}', [PostController::class, 'destroy'])
    ->middleware('can:delete,post');

Route::post('/posts/{post}/like', [LikeController::class, 'store']);
Route::delete('/posts/{post}/like', [LikeController::class, 'destroy']);
