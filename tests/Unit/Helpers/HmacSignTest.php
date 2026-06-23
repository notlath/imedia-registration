<?php
declare(strict_types=1);

namespace IMF\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

final class HmacSignTest extends TestCase
{
    public function test_basic_sign(): void
    {
        $result = \imf_hmac_sign('{"a":1}', 'secret');
        $this->assertStringStartsWith('sha256=', $result);
        $this->assertSame(71, strlen($result));
    }

    public function test_empty_body(): void
    {
        $result = \imf_hmac_sign('', 'secret');
        $this->assertStringStartsWith('sha256=', $result);
    }

    public function test_different_secrets_produce_different_signatures(): void
    {
        $result1 = \imf_hmac_sign('body', 'secret1');
        $result2 = \imf_hmac_sign('body', 'secret2');
        $this->assertNotSame($result1, $result2);
    }

    public function test_deterministic(): void
    {
        $result1 = \imf_hmac_sign('body', 'secret');
        $result2 = \imf_hmac_sign('body', 'secret');
        $this->assertSame($result1, $result2);
    }

    public function test_unicode_body(): void
    {
        $result = \imf_hmac_sign('{"name":"José"}', 'secret');
        $this->assertStringStartsWith('sha256=', $result);
    }

    public function test_output_prefix_sha256(): void
    {
        $result = \imf_hmac_sign('x', 'y');
        $this->assertStringStartsWith('sha256=', $result);
    }
}
