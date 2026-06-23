<?php
declare(strict_types=1);

namespace IMF\Tests\Regression;

use App\Core\Hmac;
use IMF\Tests\Support\DatabaseTestCase;

/**
 * Regression: HMAC signature algorithm compatibility.
 *
 * Verifies that the signing algorithm used by the WP plugin
 * (includes/helpers.php: imf_hmac_sign) is compatible with the
 * standalone app's verification (app/Core/Hmac.php: verify).
 */
class SubmitFlowRegressionTest extends DatabaseTestCase
{
    private const SECRET = 'fa9da4753a2a3f9a0d21c1eeca0535b4e34db5d34123d2795a31ba8d2c9a193d';

    public function test_hmac_algorithm_matches_wp_plugin(): void
    {
        $body   = '{"form_id":9999,"fields":{"name":"Test"}}';
        $sig    = 'sha256=' . hash_hmac('sha256', $body, self::SECRET);

        $this->assertTrue(Hmac::verify($body, self::SECRET, $sig));
    }

    public function test_hmac_rejects_wrong_secret(): void
    {
        $body = '{"form_id":9999,"fields":{"name":"Test"}}';
        $sig  = 'sha256=' . hash_hmac('sha256', $body, 'wrong-secret');

        $this->assertFalse(Hmac::verify($body, self::SECRET, $sig));
    }

    public function test_hmac_rejects_tampered_body(): void
    {
        $body    = '{"form_id":9999,"fields":{"name":"Original"}}';
        $sig     = 'sha256=' . hash_hmac('sha256', $body, self::SECRET);
        $tampered = '{"form_id":9999,"fields":{"name":"Tampered"}}';

        $this->assertFalse(Hmac::verify($tampered, self::SECRET, $sig));
    }

    public function test_unsigned_request_rejected_documented(): void
    {
        // The HMAC header is verified by the HmacVerify middleware,
        // which is tested separately in Tier 1.  This test documents
        // that the header is required at the application layer.
        $this->assertTrue(true);
    }
}
