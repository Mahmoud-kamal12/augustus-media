<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $user = User::create($validatedData);
        $accessToken = $user->createToken('api');
        $plainTextToken = $accessToken->plainTextToken;
        $userResource = new UserResource($user);

        $responseData = [
            'user' => $userResource->resolve($request),
            'token' => $plainTextToken,
        ];

        return ApiResponse::created($responseData, 'User registered successfully.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $accessToken = $user->createToken('api');
        $plainTextToken = $accessToken->plainTextToken;
        $userResource = new UserResource($user);

        $responseData = [
            'user' => $userResource->resolve($request),
            'token' => $plainTextToken,
        ];

        return ApiResponse::ok($responseData, 'User logged in successfully.');
    }
}
