<?php
declare(strict_types=1);

namespace IMF\Tests\Regression;

use App\Core\{Request, Response, Router, Session};
use IMF\Tests\Support\DatabaseTestCase;
use App\Core\Hmac;

/**
 * @todo Implement replay protection using nonce/timestamp tracking.
 *
 * Current behavior:
 * Identical signed requests submitted within the accepted freshness
 * window are treated as independent requests.  No nonce or idempotency
 * key is checked.
 *
 * These tests document the current behavior and exist to alert on any
 * accidental change to the signature algorithm.
 */
class HMACReplayRegressionTest extends DatabaseTestCase
{
    private const SECRET = 'fa9da4753a2a3f9a0d21c1eeca0535b4e34db5d34123d2795a31ba8d2c9a193d';

    public function test_hmac_algorithm_is_stable(): void
    {
        $body = '{"form_id":9999,"fields":{"name":"Regression"}}';
        $sig  = 'sha256=' . hash_hmac('sha256', $body, self::SECRET);
        $this->assertTrue(Hmac::verify($body, self::SECRET, $sig));
    }

    public function test_hmac_rejects_wrong_secret(): void
    {
        $body = '{"form_id":9999,"fields":{"name":"Regression"}}';
        $sig  = 'sha256=' . hash_hmac('sha256', $body, 'wrong-secret');
        $this->assertFalse(Hmac::verify($body, self::SECRET, $sig));
    }

    public function test_hmac_rejects_tampered_body(): void
    {
        $original = '{"form_id":9999,"fields":{"name":"Original"}}';
        $tampered = '{"form_id":9999,"fields":{"name":"Tampered"}}';
        $sig = 'sha256=' . hash_hmac('sha256', $original, self::SECRET);
        $this->assertFalse(Hmac::verify($tampered, self::SECRET, $sig));
    }
}
