<?php

namespace App\Http\Middleware;

use App\Services\UipJwtService;
use Closure;
use Illuminate\Http\Request;

/**
 * Protects any route it's attached to: requires a valid `Authorization:
 * Bearer <access_token>` header. On success it stashes the decoded
 * user id / role on the request so controllers can read them without
 * re-decoding the token themselves.
 */
class UipAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization', '');

        if (!str_starts_with($header, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
                'data' => null,
                'errors' => null,
                'meta' => (object) [],
            ], 401);
        }

        $token = substr($header, 7);
        $claims = UipJwtService::decode($token);

        if (!$claims || !isset($claims['sub'])) {
            return response()->json([
                'success' => false,
                'message' => 'Your session has expired. Please log in again.',
                'data' => null,
                'errors' => null,
                'meta' => (object) [],
            ], 401);
        }

        $request->attributes->set('uip_user_id', (int) $claims['sub']);
        $request->attributes->set('uip_role', (string) ($claims['role'] ?? ''));

        return $next($request);
    }
}
