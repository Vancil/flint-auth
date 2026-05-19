<?php
declare(strict_types=1);

namespace Vancil\FlintAuth;

/**
 * Static store for the authenticated identity set by middleware.
 * Access the resolved user/payload anywhere after authentication.
 */
class Auth
{
    private static mixed $payload = null;

    /** Set the authenticated payload (called by middleware). */
    public static function set(mixed $payload): void
    {
        static::$payload = $payload;
    }

    /** Get the authenticated payload (JWT claims, API key, or bearer token). */
    public static function user(): mixed
    {
        return static::$payload;
    }

    /** True if a user has been authenticated on this request. */
    public static function check(): bool
    {
        return static::$payload !== null;
    }

    /** Clear the authenticated state (useful in tests). */
    public static function reset(): void
    {
        static::$payload = null;
    }
}
