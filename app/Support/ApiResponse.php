<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    public static function ok(mixed $responseData = null, string $message = 'OK', array $meta = []): JsonResponse
    {
        return self::json($responseData, $message, $meta, Response::HTTP_OK);
    }

    public static function created(mixed $responseData = null, string $message = 'Created', array $meta = []): JsonResponse
    {
        return self::json($responseData, $message, $meta, Response::HTTP_CREATED);
    }

    public static function deleted(string $message = 'Deleted'): JsonResponse
    {
        return self::json(null, $message, [], Response::HTTP_OK);
    }

    public static function error(string $message, int $statusCode, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'meta' => (object) [],
            'errors' => (object) $errors,
        ], $statusCode);
    }

    private static function json(mixed $responseData, string $message, array $meta, int $statusCode): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $responseData,
            'meta' => (object) $meta,
        ], $statusCode);
    }
}
