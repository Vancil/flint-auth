<?php
declare(strict_types=1);

namespace Tests\Unit;

use Firebase\JWT\JWT;
use Flint\Request;
use Flint\Response;
use PHPUnit\Framework\TestCase;
use Vancil\FlintAuth\Auth;
use Vancil\FlintAuth\Middleware\RefreshMiddleware;

class RefreshMiddlewareTest extends TestCase
{
    private const SECRET    = 'test-secret-key-that-is-long-enough';
    private const ALGORITHM = 'HS256';

    protected function setUp(): void
    {
        Auth::reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/';
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    public function test_valid_refresh_token_passes_through(): void
    {
        $this->setBearer($this->refreshToken());

        $response = $this->dispatch();

        $this->assertSame(200, $this->responseStatus($response));
    }

    public function test_sets_auth_user_on_valid_token(): void
    {
        $this->setBearer($this->refreshToken(['sub' => 42]));

        $this->dispatch();

        $this->assertSame(42, Auth::user()->sub);
    }

    public function test_missing_token_returns_401(): void
    {
        $response = $this->dispatch();

        $this->assertSame(401, $this->responseStatus($response));
    }

    public function test_access_token_is_rejected(): void
    {
        $token = JWT::encode([
            'sub'  => 1,
            'type' => 'access',
            'iat'  => time(),
            'exp'  => time() + 3600,
        ], self::SECRET, self::ALGORITHM);

        $this->setBearer($token);

        $response = $this->dispatch();

        $this->assertSame(401, $this->responseStatus($response));
    }

    public function test_token_without_type_is_rejected(): void
    {
        $token = JWT::encode([
            'sub' => 1,
            'iat' => time(),
            'exp' => time() + 3600,
        ], self::SECRET, self::ALGORITHM);

        $this->setBearer($token);

        $response = $this->dispatch();

        $this->assertSame(401, $this->responseStatus($response));
    }

    public function test_expired_token_returns_401(): void
    {
        $token = JWT::encode([
            'sub'  => 1,
            'type' => 'refresh',
            'iat'  => time() - 7200,
            'exp'  => time() - 3600,
        ], self::SECRET, self::ALGORITHM);

        $this->setBearer($token);

        $response = $this->dispatch();

        $this->assertSame(401, $this->responseStatus($response));
    }

    public function test_invalid_signature_returns_401(): void
    {
        $token = JWT::encode(
            ['sub' => 1, 'type' => 'refresh', 'exp' => time() + 3600],
            'wrong-secret',
            self::ALGORITHM
        );

        $this->setBearer($token);

        $response = $this->dispatch();

        $this->assertSame(401, $this->responseStatus($response));
    }

    // Helpers

    private function refreshToken(array $extra = []): string
    {
        return JWT::encode(array_merge([
            'sub'  => 1,
            'type' => 'refresh',
            'iat'  => time(),
            'exp'  => time() + (86400 * 30),
        ], $extra), self::SECRET, self::ALGORITHM);
    }

    private function dispatch(): Response
    {
        $middleware = new RefreshMiddleware();
        $request    = new Request();
        $next       = fn(Request $req) => Response::json(['ok' => true]);

        return $middleware->handle($request, $next);
    }

    private function setBearer(string $token): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    }

    private function responseStatus(Response $response): int
    {
        return (new \ReflectionProperty($response, 'status'))->getValue($response);
    }
}
