<?php

/**
 * IMedia Registration — CsrfVerify middleware.
 *
 * Verifies the _csrf form field against the session token (Csrf::token()).
 * Returns 419 on mismatch. Always place AFTER AdminAuth so the user is
 * already authenticated (otherwise unauthenticated requests would burn
 * the token on a 302).
 *
 * Per php-pro: timing-safe via hash_equals.
 * Per wordpress-pro: matches the WP nonce semantics.
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\{Csrf, Request, Response, Session};

final class CsrfVerify
{
    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        $supplied = (string) $req->input('_csrf', '');
        if (!Csrf::verify($supplied)) {
            // On failure, regenerate the token so a stale token cannot be reused.
            Session::forget('_csrf_token');
            return $res->error(419, 'Security check failed. Please reload the form and try again.');
        }
        return $next($req, $res);
    }
}
