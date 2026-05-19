<?php
declare(strict_types=1);

namespace Vancil\FlintAuth\Middleware;

use Closure;
use Flint\Request;
use Flint\Response;
use Vancil\FlintAuth\Auth;

/**
 * Validates an API key passed via a request header or query string.
 * Valid keys are defined in config('auth.api_keys') as a comma-separated list in API_KEYS.
 */
class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $headerName = config('auth.api_key_header') ?: 'X-API-Key';
        $key        = $request->header($headerName) ?? $request->input('api_key');
        $validKeys  = config('auth.api_keys') ?: [];

        if (!$key || empty($validKeys)) {
            return Response::json(['error' => 'Unauthorized.'], 401);
        }

        $matched = array_filter(
            $validKeys,
            fn(string $valid) => hash_equals($valid, $key)
        );

        if (empty($matched)) {
            return Response::json(['error' => 'Unauthorized.'], 401);
        }

        Auth::set($key);

        return $next($request);
    }
}
