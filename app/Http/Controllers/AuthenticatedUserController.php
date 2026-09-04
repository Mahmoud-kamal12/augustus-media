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
        return ApiResponse::ok(
            (new UserResource($request->user()))->resolve($request),
            'Authenticated user fetched.'
        );
    }
}
