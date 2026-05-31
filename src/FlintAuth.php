<?php
declare(strict_types=1);

namespace Vancil\FlintAuth;

use Flint\Application;
use Vancil\FlintAuth\Commands\UiCommand;

class FlintAuth
{
    public static function register(Application $app): void
    {
        // Session, CSRF, and auth middleware are provided by Flint core.
        // This package's role is scaffolding — no runtime services to register.
    }

    public static function commands(Application $app): array
    {
        return [
            new UiCommand($app),
        ];
    }
}
