<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Every API response uses this same envelope — { success, message, data,
 * errors, meta } — which is exactly what the React app's api/client.js
 * expects (it throws an ApiError whenever success === false).
 */
abstract class Controller
{
    protected function apiSuccess($data = null, string $message = 'Operation completed successfully.', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'meta' => (object) [],
        ], $status);
    }

    protected function apiError(string $message, ?array $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
            'meta' => (object) [],
        ], $status);
    }
}
