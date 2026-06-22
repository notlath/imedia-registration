<?php

/**
 * IMedia Registration — LoginAttempt model.
 *
 * Phase 8: throttles repeated failed logins so credential-stuffing or
 * brute-force attempts can't go unnoticed. The store is a single flat
 * table; we never read the full history, only the recent window.
 *
 * Per database-optimizer:
 *   - The IP column is VARBINARY(16) + indexed so inet_pton('1.2.3.4')
 *     is a fast equality match and the (ip, created_at) composite index
 *     serves the rolling window count.
 *   - The 1h account-side limit also uses an (email, created_at) index.
 *
 * Cleanup is a single DELETE older than 24h; called by the worker cron
 * once an hour. LoginController never blocks on cleanup.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class LoginAttempt
{
    /** Per-IP threshold: 5 failures inside 15 min. */
    public const IP_WINDOW        = 900;
    public const IP_MAX_FAILURES  = 5;

    /** Per-account threshold: 10 failures inside 1 h. */
    public const EMAIL_WINDOW     = 3600;
    public const EMAIL_MAX_FAILURES = 10;

    /** Once tripped, the account is locked for 1 h. */
    public const LOCKOUT_SECONDS  = 3600;

    public static function recordFailure(string $ip, string $email): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO login_attempts (ip, email, success) VALUES (:ip, :email, 0)'
        );
        $stmt->bindValue(':ip', self::packIp($ip));
        $stmt->bindValue(':email', $email);
        $stmt->execute();
    }

    public static function recordSuccess(string $ip, string $email): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO login_attempts (ip, email, success) VALUES (:ip, :email, 1)'
        );
        $stmt->bindValue(':ip', self::packIp($ip));
        $stmt->bindValue(':email', $email);
        $stmt->execute();
    }

    public static function recentFailuresByIp(string $ip): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE ip = :ip AND success = 0 AND created_at >= (NOW() - INTERVAL :w SECOND)'
        );
        $stmt->bindValue(':ip', self::packIp($ip));
        $stmt->bindValue(':w', self::IP_WINDOW, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public static function recentFailuresByEmail(string $email): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE email = :email AND success = 0 AND created_at >= (NOW() - INTERVAL :w SECOND)'
        );
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':w', self::EMAIL_WINDOW, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Sweep rows older than 24 h. Cheap; runs from the worker cron
     * once an hour. Bounded by the (created_at) trailing edge of the
     * two composite indexes.
     */
    public static function purgeOlderThan(int $seconds = 86400): int
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL :s SECOND)'
        );
        $stmt->bindValue(':s', $seconds, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Pack an IPv4 or IPv6 string into 16 bytes for VARBINARY storage.
     * Returns the raw 16-byte string (zeros for invalid input) so the
     * column is always a fixed length and the index is selective.
     */
    private static function packIp(string $ip): string
    {
        $packed = @inet_pton($ip);
        if ($packed === false || strlen($packed) !== 16) {
            return str_repeat("\0", 16);
        }
        return $packed;
    }
}
