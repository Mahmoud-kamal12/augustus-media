<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FeedCache;
use App\Services\FollowService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FollowController extends Controller
{
    public function __construct(
        private readonly FollowService $followService,
        private readonly FeedCache $feedCache,
    ) {}

    public function store(Request $request, User $userToFollow): JsonResponse
    {
        $currentUser = $request->user();

        if ($currentUser->is($userToFollow)) {
            throw ValidationException::withMessages([
                'user_id' => ['You cannot follow yourself.'],
            ]);
        }

        if ($this->followService->follow($currentUser, $userToFollow)) {
            $this->feedCache->forgetFirstPage($currentUser);
        }

        return ApiResponse::ok([
            'followed' => true,
        ], 'User followed successfully.');
    }

    public function destroy(Request $request, User $userToUnfollow): JsonResponse
    {
        $currentUser = $request->user();

        if ($this->followService->unfollow($currentUser, $userToUnfollow)) {
            $this->feedCache->forgetFirstPage($currentUser);
        }

        return ApiResponse::deleted('User unfollowed successfully.');
    }
}
