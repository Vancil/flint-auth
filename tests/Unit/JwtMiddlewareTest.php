<?php
declare(strict_types=1);

namespace Tests\Unit;

use Firebase\JWT\JWT;
use Flint\Request;
use Flint\Response;
use PHPUnit\Framework\TestCase;
use Vancil\FlintAuth\Auth;
use Vancil\FlintAuth\Middleware\JwtMiddleware;

class JwtMiddlewareTest extends TestCase
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

    public function test_valid_access_token_passes_through(): void
    {
        $this->setBearer($this->accessToken());

        $response = $this->dispatch();

        $this->assertSame(200, $this->responseStatus($response));
    }

    public function test_sets_auth_user_on_valid_token(): void
    {
        $this->setBearer($this->accessToken(['sub' => 99, 'email' => 'user@example.com']));

        $this->dispatch();

        $this->assertSame(99, Auth::user()->sub);
        $this->assertSame('user@example.com', Auth::user()->email);
    }

    public function test_missing_token_returns_401(): void
    {
        $response = $this->dispatch();

        $this->assertSame(401, $this->responseStatus($response));
    }

    public function test_refresh_token_is_rejected(): void
    {
        $this->setBearer($this->refreshToken());

        $response = $this->dispatch();

        $this->assertSame(401, $this->responseStatus($response));
    }

    public function test_expired_token_returns_401(): void
    {
        $token = JWT::encode([
            'sub'  => 1,
            'type' => 'access',
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
            ['sub' => 1, 'type' => 'access', 'exp' => time() + 3600],
            'wrong-secret',
            self::ALGORITHM
        );

        $this->setBearer($token);

        $response = $this->dispatch();

        $this->assertSame(401, $this->responseStatus($response));
    }

    public function test_error_detail_is_not_exposed(): void
    {
        $this->setBearer('not.a.valid.jwt');

        $response = $this->dispatch();

        $body = $this->responseBody($response);
        $this->assertStringNotContainsString('Wrong number', $body);
        $this->assertStringNotContainsString('Syntax error', $body);
    }

    // Helpers

    private function accessToken(array $extra = []): string
    {
        return JWT::encode(array_merge([
            'sub'  => 1,
            'type' => 'access',
            'iat'  => time(),
            'exp'  => time() + 3600,
        ], $extra), self::SECRET, self::ALGORITHM);
    }

    private function refreshToken(): string
    {
        return JWT::encode([
            'sub'  => 1,
            'type' => 'refresh',
            'iat'  => time(),
            'exp'  => time() + (86400 * 30),
        ], self::SECRET, self::ALGORITHM);
    }

    private function dispatch(): Response
    {
        $middleware = new JwtMiddleware();
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

    private function responseBody(Response $response): string
    {
        return (new \ReflectionProperty($response, 'body'))->getValue($response);
    }
}
