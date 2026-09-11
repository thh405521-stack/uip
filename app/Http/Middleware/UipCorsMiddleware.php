<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Minimal CORS handling for local development: the React app runs on its
 * own Vite dev server (default http://localhost:5173) while this API runs
 * on http://localhost:8000, so browsers treat every request as
 * cross-origin. Since auth uses a Bearer token (not cookies), we don't
 * need withCredentials/Access-Control-Allow-Credentials — just permissive
 * headers/methods for the allowed origin(s).
 *
 * Set FRONTEND_URL in .env to your deployed frontend origin in production.
 */
class UipCorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigin = env('FRONTEND_URL', 'http://localhost:5173');
        $origin = $request->header('Origin', '');

        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        $response->headers->set('Access-Control-Allow-Origin', $origin === $allowedOrigin ? $origin : $allowedOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Locale');
        $response->headers->set('Vary', 'Origin');

        return $response;
    }
}
