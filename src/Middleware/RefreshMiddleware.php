<?php
declare(strict_types=1);

namespace Vancil\FlintAuth\Middleware;

use Closure;
use Flint\QueryBuilder;
use Flint\Request;
use Flint\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Vancil\FlintAuth\Auth;

class RefreshMiddleware
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
        } catch (\Throwable) {
            return Response::json(['error' => 'Unauthorized.'], 401);
        }

        if (($payload->type ?? null) !== 'refresh') {
            return Response::json(['error' => 'Unauthorized.'], 401);
        }

        $jti = $payload->jti ?? null;

        if (!$jti) {
            return Response::json(['error' => 'Unauthorized.'], 401);
        }

        try {
            $stored = (new QueryBuilder('refresh_tokens'))
                ->where('jti', $jti)
                ->first();
        } catch (\Throwable) {
            return Response::json(['error' => 'Unauthorized.'], 401);
        }

        if (!$stored || strtotime($stored['expires_at']) < time()) {
            return Response::json(['error' => 'Unauthorized.'], 401);
        }

        Auth::set($payload);

        return $next($request);
    }
}
