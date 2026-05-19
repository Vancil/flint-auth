<?php
declare(strict_types=1);

namespace Vancil\FlintAuth\Middleware;

use Closure;
use Flint\Request;
use Flint\Response;
use Vancil\FlintAuth\Auth;

/**
 * Validates the Authorization: Bearer token against APP_SECRET.
 * Suitable for internal services and simple API protection.
 */
class BearerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token  = $request->bearerToken();
        $secret = config('app.secret');

        if (!$token || !$secret || !hash_equals($secret, $token)) {
            return Response::json(['error' => 'Unauthorized.'], 401);
        }

        Auth::set($token);

        return $next($request);
    }
}
