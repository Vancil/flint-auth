<?php
declare(strict_types=1);

namespace Tests\Unit;

use Firebase\JWT\JWT;
use Flint\Database;
use Flint\Request;
use Flint\Response;
use PDO;
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

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('
            CREATE TABLE refresh_tokens (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER NOT NULL,
                jti        TEXT    NOT NULL UNIQUE,
                expires_at TEXT    NOT NULL,
                created_at TEXT,
                updated_at TEXT
            )
        ');

        Database::setConnection($pdo);
    }

    public function test_valid_refresh_token_with_stored_jti_passes_through(): void
    {
        $jti   = 'test-jti-valid';
        $token = $this->refreshToken(['jti' => $jti]);
        $this->storeJti($jti);
        $this->setBearer($token);

        $response = $this->dispatch();

        $this->assertSame(200, $this->responseStatus($response));
    }

    public function test_sets_auth_user_on_valid_token(): void
    {
        $jti   = 'test-jti-claims';
        $token = $this->refreshToken(['jti' => $jti, 'sub' => 42]);
        $this->storeJti($jti);
        $this->setBearer($token);

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
            'exp'  => time() + 900,
        ], self::SECRET, self::ALGORITHM);

        $this->setBearer($token);

        $this->assertSame(401, $this->responseStatus($this->dispatch()));
    }

    public function test_token_without_type_is_rejected(): void
    {
        $token = JWT::encode([
            'sub' => 1,
            'jti' => 'some-jti',
            'exp' => time() + 3600,
        ], self::SECRET, self::ALGORITHM);

        $this->setBearer($token);

        $this->assertSame(401, $this->responseStatus($this->dispatch()));
    }

    public function test_token_without_jti_is_rejected(): void
    {
        $token = JWT::encode([
            'sub'  => 1,
            'type' => 'refresh',
            'exp'  => time() + (86400 * 30),
        ], self::SECRET, self::ALGORITHM);

        $this->setBearer($token);

        $this->assertSame(401, $this->responseStatus($this->dispatch()));
    }

    public function test_unknown_jti_returns_401(): void
    {
        $token = $this->refreshToken(['jti' => 'not-in-db']);
        $this->setBearer($token);

        $this->assertSame(401, $this->responseStatus($this->dispatch()));
    }

    public function test_expired_db_record_returns_401(): void
    {
        $jti   = 'test-jti-expired-db';
        $token = $this->refreshToken(['jti' => $jti]);
        $this->storeJti($jti, time() - 3600);
        $this->setBearer($token);

        $this->assertSame(401, $this->responseStatus($this->dispatch()));
    }

    public function test_expired_jwt_returns_401(): void
    {
        $jti   = 'test-jti-expired-jwt';
        $token = JWT::encode([
            'sub'  => 1,
            'type' => 'refresh',
            'jti'  => $jti,
            'iat'  => time() - 7200,
            'exp'  => time() - 3600,
        ], self::SECRET, self::ALGORITHM);

        $this->storeJti($jti);
        $this->setBearer($token);

        $this->assertSame(401, $this->responseStatus($this->dispatch()));
    }

    public function test_invalid_signature_returns_401(): void
    {
        $token = JWT::encode(
            ['sub' => 1, 'type' => 'refresh', 'jti' => 'x', 'exp' => time() + 3600],
            'wrong-secret',
            self::ALGORITHM
        );

        $this->setBearer($token);

        $this->assertSame(401, $this->responseStatus($this->dispatch()));
    }

    // Helpers

    private function refreshToken(array $extra = []): string
    {
        return JWT::encode(array_merge([
            'sub'  => 1,
            'type' => 'refresh',
            'jti'  => 'default-jti',
            'iat'  => time(),
            'exp'  => time() + (86400 * 30),
        ], $extra), self::SECRET, self::ALGORITHM);
    }

    private function storeJti(string $jti, int $expiresAt = 0): void
    {
        $expiresAt = $expiresAt ?: time() + (86400 * 30);
        Database::connection()->exec(
            "INSERT INTO refresh_tokens (user_id, jti, expires_at) VALUES (1, '{$jti}', '" . date('Y-m-d H:i:s', $expiresAt) . "')"
        );
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
