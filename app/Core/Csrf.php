<?php

/**
 * IMedia Registration — CSRF token generate/verify.
 *
 * Per php-pro: timing-safe comparison via hash_equals.
 * Per wordpress-pro: tokens are stored in the session and re-generated
 * on every page load (so an attacker can't reuse a stolen token).
 *
 * Usage in a view:
 *   <form>
 *     <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
 *     ...
 *   </form>
 *
 *   if (!Csrf::verify($request->input('_csrf'))) {
 *       return $response->error(419, 'Security check failed.');
 *   }
 */

declare(strict_types=1);

namespace App\Core;

final class Csrf {
    private const SESSION_KEY = '_csrf_token';

    /**
     * Return the current CSRF token, generating one if none exists.
     */
    public static function token(): string {
        $existing = Session::get(self::SESSION_KEY);
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }
        $token = bin2hex(random_bytes(32));
        Session::put(self::SESSION_KEY, $token);
        return $token;
    }

    /**
     * Verify a token from a request against the session token.
     * Returns true on match, false on mismatch / missing.
     */
    public static function verify( ?string $supplied ): bool {
        if (! is_string($supplied) || $supplied === '') {
            return false;
        }
        $stored = Session::get(self::SESSION_KEY);
        if (! is_string($stored) || $stored === '') {
            return false;
        }
        return hash_equals($stored, $supplied);
    }
}
