<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => (object) $meta,
        ], $status);
    }

    public static function error(string $message, int $status = 400, mixed $errors = null, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'meta' => (object) $meta,
        ], $status);
    }
}
