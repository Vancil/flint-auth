<?php
declare(strict_types=1);

namespace Vancil\FlintAuth;

use Flint\Application;
use Flint\Router;
use Vancil\FlintAuth\Commands\AuthInstall;
use Vancil\FlintAuth\Middleware\JwtMiddleware;
use Vancil\FlintAuth\Middleware\RefreshMiddleware;

class FlintAuth
{
    public static function register(Application $app): void
    {
        $router = $app->make(Router::class);
        $router->addMiddlewareAlias('auth.jwt',     JwtMiddleware::class);
        $router->addMiddlewareAlias('auth.refresh', RefreshMiddleware::class);
    }

    public static function commands(Application $app): array
    {
        return [
            new AuthInstall($app),
        ];
    }
}
