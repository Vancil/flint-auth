<?php
declare(strict_types=1);

namespace Vancil\FlintAuth;

use Flint\Application;
use Flint\Router;
use Vancil\FlintAuth\Commands\AuthInstall;
use Vancil\FlintAuth\Middleware\BearerMiddleware;
use Vancil\FlintAuth\Middleware\JwtMiddleware;
use Vancil\FlintAuth\Middleware\ApiKeyMiddleware;

/**
 * Registers all flint-auth middleware aliases and CLI commands.
 * Add to config/app.php packages array — no other setup needed.
 */
class FlintAuth
{
    public static function register(Application $app): void
    {
        $router = $app->make(Router::class);
        $router->addMiddlewareAlias('auth.bearer', BearerMiddleware::class);
        $router->addMiddlewareAlias('auth.jwt',    JwtMiddleware::class);
        $router->addMiddlewareAlias('auth.apikey', ApiKeyMiddleware::class);
    }

    public static function commands(Application $app): array
    {
        return [
            new AuthInstall($app),
        ];
    }
}
