<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vancil\FlintAuth\Auth;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        Auth::reset();
    }

    public function test_user_returns_null_by_default(): void
    {
        $this->assertNull(Auth::user());
    }

    public function test_check_returns_false_when_not_set(): void
    {
        $this->assertFalse(Auth::check());
    }

    public function test_set_stores_payload(): void
    {
        Auth::set('token-value');

        $this->assertSame('token-value', Auth::user());
    }

    public function test_check_returns_true_after_set(): void
    {
        Auth::set('token-value');

        $this->assertTrue(Auth::check());
    }

    public function test_set_accepts_object_payload(): void
    {
        $claims = (object) ['sub' => 42, 'email' => 'user@example.com'];
        Auth::set($claims);

        $this->assertSame(42, Auth::user()->sub);
    }

    public function test_reset_clears_payload(): void
    {
        Auth::set('token-value');
        Auth::reset();

        $this->assertNull(Auth::user());
        $this->assertFalse(Auth::check());
    }
}
