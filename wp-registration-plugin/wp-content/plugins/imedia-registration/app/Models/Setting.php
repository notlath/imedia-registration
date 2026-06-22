<?php

/**
 * IMedia Registration — Setting model.
 *
 * The settings table is a singleton row (id = 1). Phase 3 introduces
 * this model so that HmacVerify, ThresholdChecker, and the dashboard
 * stats all read from one place. Per database-optimizer, the row is
 * cached in a static property for the duration of the request — at
 * most one DB read per request for the entire settings table.
 *
 * Per php-pro: strict types, named placeholders, typed accessors.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Setting
{
    /** @var array<string, mixed>|null Cached row for the request lifecycle. */
    private static ?array $row = null;

    /** @var bool Tracks whether a write happened; forces a re-read on next get(). */
    private static bool $dirty = false;

    /**
     * Return a value from the settings row, or a default.
     * Always reads from the cached row (one DB hit per request).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $row = self::load();
        return array_key_exists($key, $row) ? $row[$key] : $default;
    }

    /**
     * Update one column. Marks the cache dirty so the next get() re-reads.
     */
    public static function put(string $key, mixed $value): void
    {
        // Ensure the row exists.
        self::load();
        $stmt = Database::pdo()->prepare('UPDATE settings SET ' . self::safeColumn($key) . ' = :v WHERE id = 1');
        $stmt->execute([':v' => $value]);
        self::$dirty = true;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return self::load();
    }

    /**
     * Force a fresh read of the row, bypassing the cache.
     */
    public static function refresh(): array
    {
        self::$row   = null;
        self::$dirty = false;
        return self::load();
    }

    /**
     * Load the singleton row, using a cached copy if available.
     * Inserts a default row if none exists (idempotent).
     *
     * @return array<string, mixed>
     */
    private static function load(): array
    {
        if (self::$row !== null && !self::$dirty) {
            return self::$row;
        }
        $stmt = Database::pdo()->query('SELECT * FROM settings WHERE id = 1 LIMIT 1');
        $row = $stmt ? ($stmt->fetch() ?: []) : [];
        if ($row === []) {
            // First-run safety: insert a default row.
            $insert = Database::pdo()->prepare(
                'INSERT INTO settings (id) VALUES (1) ON DUPLICATE KEY UPDATE id = id'
            );
            $insert->execute();
            $row = ['id' => 1];
        }
        self::$row   = $row;
        self::$dirty = false;
        return self::$row;
    }

    /**
     * Whitelist column names to prevent SQL injection via the key name itself.
     */
    private static function safeColumn(string $key): string
    {
        if (!preg_match('/^[a-z_][a-z0-9_]{0,63}$/i', $key)) {
            throw new \InvalidArgumentException("Invalid settings column name: {$key}");
        }
        return '`' . $key . '`';
    }
}
