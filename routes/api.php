<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/posts/{post}', [PostController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/feed', [FeedController::class, 'index']);

    Route::post('/follow/{user_id}', [FollowController::class, 'store']);
    Route::delete('/follow/{user_id}', [FollowController::class, 'destroy']);

    Route::post('/posts', [PostController::class, 'store']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);

    Route::post('/posts/{post}/like', [LikeController::class, 'store']);
    Route::delete('/posts/{post}/like', [LikeController::class, 'destroy']);
});
