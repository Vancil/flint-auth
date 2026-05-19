<?php
declare(strict_types=1);

namespace Vancil\FlintAuth;

use Flint\Router;
use Vancil\FlintAuth\Middleware\BearerMiddleware;
use Vancil\FlintAuth\Middleware\JwtMiddleware;
use Vancil\FlintAuth\Middleware\ApiKeyMiddleware;

/**
 * Registers all flint-auth middleware aliases with the router.
 * Call FlintAuth::register($router) in your application boot.
 */
class FlintAuth
{
    public static function register(Router $router): void
    {
        $router->addMiddlewareAlias('auth.bearer', BearerMiddleware::class);
        $router->addMiddlewareAlias('auth.jwt',    JwtMiddleware::class);
        $router->addMiddlewareAlias('auth.apikey', ApiKeyMiddleware::class);
    }
}
