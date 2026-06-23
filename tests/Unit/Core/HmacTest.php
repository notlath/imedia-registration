<?php
declare(strict_types=1);

namespace IMF\Tests\Unit\Core;

use App\Core\Hmac;
use IMF\Tests\Support\AppTestCase;

/**
 * Tier 1 — Pure unit tests for Hmac::verify().
 *
 * @covers \App\Core\Hmac
 */
class HmacTest extends AppTestCase
{
    private const SECRET = 'test-secret-123';
    private const BODY   = '{"form_id":9999,"fields":{"name":"Test"}}';

    public function test_verify_returns_true_for_valid_signature(): void
    {
        $sig = 'sha256=' . hash_hmac('sha256', self::BODY, self::SECRET);
        $this->assertTrue(Hmac::verify(self::BODY, self::SECRET, $sig));
    }

    public function test_verify_returns_false_for_bad_signature(): void
    {
        $this->assertFalse(Hmac::verify(self::BODY, self::SECRET, 'sha256=0000000000000000'));
    }

    public function test_verify_returns_false_for_empty_header(): void
    {
        $this->assertFalse(Hmac::verify(self::BODY, self::SECRET, ''));
    }

    public function test_verify_rejects_header_with_wrong_prefix(): void
    {
        $hash = hash_hmac('sha256', self::BODY, self::SECRET);
        $this->assertFalse(Hmac::verify(self::BODY, self::SECRET, 'md5=' . $hash));
    }

    public function test_verify_rejects_wrong_secret(): void
    {
        $sig = 'sha256=' . hash_hmac('sha256', self::BODY, 'wrong-secret');
        $this->assertFalse(Hmac::verify(self::BODY, self::SECRET, $sig));
    }

    public function test_verify_returns_true_for_unicode_body(): void
    {
        $body = '{"name":"José"}';
        $sig  = 'sha256=' . hash_hmac('sha256', $body, self::SECRET);
        $this->assertTrue(Hmac::verify($body, self::SECRET, $sig));
    }

    public function test_verify_timing_safe(): void
    {
        $sig = 'sha256=' . hash_hmac('sha256', self::BODY, self::SECRET);
        // hash_equals is the underlying comparison; this is a sanity check.
        $this->assertTrue(Hmac::verify(self::BODY, self::SECRET, $sig));
        // A flipped byte — must still return false
        $badChars = $sig;
        $badChars[7] = $sig[7] === 'a' ? 'b' : 'a';
        $this->assertFalse(Hmac::verify(self::BODY, self::SECRET, $badChars));
    }
}
