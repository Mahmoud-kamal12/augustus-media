<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiExceptionRenderer
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render([self::class, 'validation']);
        $exceptions->render([self::class, 'authentication']);
        $exceptions->render([self::class, 'authorization']);
        $exceptions->render([self::class, 'accessDenied']);
        $exceptions->render([self::class, 'modelNotFound']);
        $exceptions->render([self::class, 'routeNotFound']);
    }

    public static function validation(ValidationException $exception, Request $request): ?JsonResponse
    {
        if (! self::shouldReturnJson($request)) {
            return null;
        }

        return ApiResponse::error(
            $exception->getMessage(),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $exception->errors()
        );
    }

    public static function authentication(AuthenticationException $exception, Request $request): ?JsonResponse
    {
        if (! self::shouldReturnJson($request)) {
            return null;
        }

        return ApiResponse::error('Unauthenticated.', Response::HTTP_UNAUTHORIZED);
    }

    public static function authorization(AuthorizationException $exception, Request $request): ?JsonResponse
    {
        if (! self::shouldReturnJson($request)) {
            return null;
        }

        return ApiResponse::error('This action is unauthorized.', Response::HTTP_FORBIDDEN);
    }

    public static function accessDenied(AccessDeniedHttpException $exception, Request $request): ?JsonResponse
    {
        if (! self::shouldReturnJson($request)) {
            return null;
        }

        return ApiResponse::error('This action is unauthorized.', Response::HTTP_FORBIDDEN);
    }

    public static function modelNotFound(ModelNotFoundException $exception, Request $request): ?JsonResponse
    {
        if (! self::shouldReturnJson($request)) {
            return null;
        }

        return ApiResponse::error('Resource not found.', Response::HTTP_NOT_FOUND);
    }

    public static function routeNotFound(NotFoundHttpException $exception, Request $request): ?JsonResponse
    {
        if (! self::shouldReturnJson($request)) {
            return null;
        }

        return ApiResponse::error('Resource not found.', Response::HTTP_NOT_FOUND);
    }

    private static function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson();
    }
}
