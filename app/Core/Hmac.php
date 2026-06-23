<?php

/**
 * IMedia Registration — HMAC verification helper.
 *
 * Used by the admin app's HmacVerify middleware to verify requests
 * signed by the WordPress plugin. The WP plugin uses its own
 * `imf_hmac_sign()` helper (`includes/helpers.php`); the standalone
 * only ever verifies, never signs.
 *
 * Per php-pro: timing-safe comparison via hash_equals.
 */

declare(strict_types=1);

namespace App\Core;

final class Hmac {
    public const ALGO = 'sha256';

    /**
     * Verify a supplied header value against the expected signature. Returns
     * true on match.
     */
    public static function verify( string $body, string $secret, string $header ): bool {
        $expected = self::ALGO . '=' . hash_hmac(self::ALGO, $body, $secret);
        return hash_equals($expected, $header);
    }
}
