<?php
declare(strict_types=1);

namespace Vancil\FlintAuth\Middleware;

use Closure;
use Flint\Request;
use Flint\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Vancil\FlintAuth\Auth;

/**
 * Validates a signed JWT from the Authorization: Bearer header.
 * Decoded claims are available via Auth::user() after authentication.
 */
class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return Response::json(['error' => 'Unauthorized.'], 401);
        }

        $secret    = config('auth.jwt_secret') ?: env('JWT_SECRET');
        $algorithm = config('auth.jwt_algorithm') ?: 'HS256';

        if (!$secret) {
            throw new \RuntimeException('JWT_SECRET is not configured. Set it in your .env file.');
        }

        try {
            $payload = JWT::decode($token, new Key($secret, $algorithm));
            Auth::set($payload);
        } catch (\Throwable) {
            return Response::json(['error' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
