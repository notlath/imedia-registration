<?php
declare(strict_types=1);

namespace IMF\Tests\Unit\Core;

use App\Core\Csrf;
use App\Core\Session;
use IMF\Tests\Support\AppTestCase;

/**
 * Tier 1 — Pure unit tests for Csrf::token() and Csrf::verify().
 *
 * @covers \App\Core\Csrf
 */
class CsrfTest extends AppTestCase
{
    private const KEY = '_csrf_token';

    protected function setUp(): void
    {
        parent::setUp();
        Session::start();
        Session::forget(self::KEY);
    }

    public function test_token_returns_64_char_hex_string(): void
    {
        $token = Csrf::token();
        $this->assertSame(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function test_token_is_stable_within_session(): void
    {
        $first  = Csrf::token();
        $second = Csrf::token();
        $this->assertSame($first, $second);
    }

    public function test_token_changes_after_regenerate(): void
    {
        $first = Csrf::token();
        Session::forget(self::KEY);
        $second = Csrf::token();
        $this->assertNotSame($first, $second);
    }

    public function test_verify_accepts_matching_token(): void
    {
        $token = Csrf::token();
        $this->assertTrue(Csrf::verify($token));
    }

    public function test_verify_rejects_non_matching_token(): void
    {
        Csrf::token();
        $this->assertFalse(Csrf::verify('0000000000000000000000000000000000000000000000000000000000000000'));
    }

    public function test_verify_rejects_empty_string(): void
    {
        $this->assertFalse(Csrf::verify(''));
    }

    public function test_verify_rejects_null(): void
    {
        $this->assertFalse(Csrf::verify(null));
    }
}
