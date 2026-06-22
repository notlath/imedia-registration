<?php

/**
 * IMedia Registration — HMAC verification middleware.
 *
 * Reads the X-IMF-Signature header on /api/submit and verifies the raw
 * request body using the shared secret stored in settings.hmac_shared_secret.
 *
 * Phase 7: also enforces a freshness window via the _imf_timestamp field
 * inside the body. The signature covers the entire body (including the
 * timestamp), so an attacker cannot tamper with the timestamp without
 * invalidating the signature. A request older than 300 seconds in either
 * direction is rejected — defends against replay attacks.
 *
 * Per php-pro: typed, no globals; settings read via the Setting model.
 * Per security: log every failed attempt (audit trail).
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\{Hmac, Logger, Request, Response};
use App\Models\Setting;

final class HmacVerify
{
    /**
     * Maximum allowed clock skew (forward or backward) between the WP
     * plugin's timestamp and the standalone's clock, in seconds.
     *
     * 5 minutes is generous for the deployment described in the README
     * (same host, same clock) and short enough to bound replay risk.
     */
    private const MAX_SKEW_SECONDS = 300;

    public function __invoke(Request $req, Response $res, callable $next): Response
    {
        $header = $req->header('x-imf-signature');
        if (!is_string($header) || $header === '') {
            Logger::warning('hmac.missing_header', [
                'ip'   => $req->ip(),
                'path' => $req->path,
            ]);
            return $res->error(401, 'Missing X-IMF-Signature header.');
        }

        try {
            $secret = Setting::get('hmac_shared_secret');
        } catch (\Throwable $e) {
            Logger::error('hmac.secret_load_failed', [
                'ip'    => $req->ip(),
                'error' => $e->getMessage(),
            ]);
            return $res->json([
                'success' => false,
                'error'   => 'database_unavailable',
                'message' => 'The application database is not reachable. '
                    . 'Check the credentials in config/config.php.',
            ], 503);
        }
        if (!is_string($secret) || $secret === '') {
            Logger::error('hmac.secret_not_configured', ['ip' => $req->ip()]);
            return $res->json([
                'success' => false,
                'error'   => 'hmac_secret_not_configured',
                'message' => 'The application has not been configured with an HMAC shared secret. '
                    . 'Open admin Settings and set the Shared Secret.',
            ], 500);
        }

        $body = $req->rawBody();
        if (!Hmac::verify($body, $secret, $header)) {
            Logger::warning('hmac.verify_failed', [
                'ip'   => $req->ip(),
                'path' => $req->path,
            ]);
            return $res->error(401, 'Invalid signature.');
        }

        // Signature OK. Now enforce replay protection: the body must
        // include an _imf_timestamp field, must be a Unix integer, and
        // must be within MAX_SKEW_SECONDS of the current server time.
        //
        // We do this AFTER signature verification so a missing/stale
        // timestamp is not used as an oracle to confirm valid signing.
        $tsField = $req->body['_imf_timestamp'] ?? null;
        if (!is_int($tsField) && !(is_string($tsField) && ctype_digit($tsField))) {
            Logger::warning('hmac.timestamp_missing', [
                'ip'   => $req->ip(),
                'path' => $req->path,
            ]);
            return $res->error(401, 'Missing or invalid _imf_timestamp.');
        }
        $ts = (int) $tsField;
        $now = time();
        if (abs($now - $ts) > self::MAX_SKEW_SECONDS) {
            Logger::warning('hmac.timestamp_stale', [
                'ip'             => $req->ip(),
                'path'           => $req->path,
                'ts'             => $ts,
                'now'            => $now,
                'skew_seconds'   => $now - $ts,
            ]);
            return $res->error(401, 'Request timestamp is outside the allowed freshness window.');
        }

        return $next($req, $res);
    }
}
