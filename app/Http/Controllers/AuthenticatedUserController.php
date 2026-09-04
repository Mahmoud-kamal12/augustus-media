<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthenticatedUserController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $userResource = new UserResource($user);
        $authenticatedUserData = $userResource->resolve($request);

        return ApiResponse::ok($authenticatedUserData, 'Authenticated user fetched.');
    }
}
