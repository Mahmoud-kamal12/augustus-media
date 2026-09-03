<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FollowController extends Controller
{
    public function __construct(private FeedService $feeds) {}

    public function store(int $user_id): JsonResponse
    {
        $currentUser = request()->user();
        $targetUser = User::query()->findOrFail($user_id);

        if ($currentUser->is($targetUser)) {
            throw ValidationException::withMessages([
                'user_id' => ['You cannot follow yourself.'],
            ]);
        }

        $inserted = DB::table('follows')->insertOrIgnore([
            'follower_id' => $currentUser->id,
            'followed_id' => $targetUser->id,
            'created_at' => now(),
        ]);

        if ($inserted === 1) {
            $this->feeds->forgetFirstPage($currentUser);
        }

        return response()->json([
            'followed' => true,
        ]);
    }

    public function destroy(int $user_id): Response
    {
        $currentUser = request()->user();
        User::query()->findOrFail($user_id);

        $deleted = DB::table('follows')
            ->where('follower_id', $currentUser->id)
            ->where('followed_id', $user_id)
            ->delete();

        if ($deleted > 0) {
            $this->feeds->forgetFirstPage($currentUser);
        }

        return response()->noContent();
    }
}
