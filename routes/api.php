<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'currentUser']);
Route::get('/posts/{post}', [PostController::class, 'show']);

Route::middleware('auth:sanctum')->get('/feed', [FeedController::class, 'index']);
Route::middleware('auth:sanctum')->post('/follow/{userToFollow}', [FollowController::class, 'store']);
Route::middleware('auth:sanctum')->delete('/follow/{userToUnfollow}', [FollowController::class, 'destroy']);
Route::middleware('auth:sanctum')->post('/posts', [PostController::class, 'store']);
Route::middleware('auth:sanctum')->delete('/posts/{post}', [PostController::class, 'destroy']);
Route::middleware('auth:sanctum')->post('/posts/{post}/like', [LikeController::class, 'store']);
Route::middleware('auth:sanctum')->delete('/posts/{post}/like', [LikeController::class, 'destroy']);
