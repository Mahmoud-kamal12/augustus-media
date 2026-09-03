<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FollowController extends Controller
{
    public function store(int $user_id): JsonResponse
    {
        $currentUser = request()->user();
        $targetUser = User::query()->findOrFail($user_id);

        if ($currentUser->is($targetUser)) {
            throw ValidationException::withMessages([
                'user_id' => ['You cannot follow yourself.'],
            ]);
        }

        DB::table('follows')->insertOrIgnore([
            'follower_id' => $currentUser->id,
            'followed_id' => $targetUser->id,
            'created_at' => now(),
        ]);

        return response()->json([
            'followed' => true,
        ]);
    }

    public function destroy(int $user_id): Response
    {
        $currentUser = request()->user();
        User::query()->findOrFail($user_id);

        DB::table('follows')
            ->where('follower_id', $currentUser->id)
            ->where('followed_id', $user_id)
            ->delete();

        return response()->noContent();
    }
}
