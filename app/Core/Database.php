<?php

/**
 * IMedia Registration — PDO singleton + transaction helper.
 *
 * Per database-optimizer skill:
 *   - Persistent connection (PDO::ATTR_PERSISTENT) so PHP reuses the
 *     socket across requests on cPanel.
 *   - Real prepared statements (PDO::ATTR_EMULATE_PREPARES = false).
 *   - Exceptions on error (PDO::ERRMODE_EXCEPTION).
 *
 * Per php-pro: strict types, final class, typed return values.
 *
 * Per mysql skill: connections use utf8mb4 and the InnoDB engine
 * (set at table creation, not connection level — InnoDB is the default
 * in MySQL 8+).
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database {
    private static ?PDO $pdo = null;

    /**
     * Return the shared PDO instance. Opens it on first use.
     *
     * Throws \RuntimeException on connection failure (caller-friendly).
     */
    public static function pdo(): PDO {
        if (self::$pdo === null) {
            try {
                self::$pdo = self::open();
            } catch (\PDOException $e) {
                Logger::error(
                    'database.connection_failed',
                    array(
                        'error' => $e->getMessage(),
                    )
                );
                throw new \RuntimeException(
                    'Could not connect to the database. Check the credentials in config/config.php.',
                    0,
                    $e
                );
            }
        }
        return self::$pdo;
    }

    /**
     * Execute a callback with the connection temporarily set to unbuffered
     * mode, then restore buffered mode before returning. This keeps the
     * shared persistent connection safe for normal queries while still
     * letting large result sets (CSV exports, etc.) stream straight from
     * MySQL to the wire without doubling their memory footprint.
     *
     * Caveat: while unbuffered, the same connection can only run one
     * statement at a time, and fetch() must consume the full result set
     * before the next query. The callback is expected to do exactly that.
     */
    public static function unbuffered( callable $fn ): mixed {
        $pdo = self::pdo();
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        try {
            return $fn($pdo);
        } finally {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
    }

    private static function open(): PDO {
        $host    = (string) Config::require('DB_HOST');
        $name    = (string) Config::require('DB_NAME');
        $user    = (string) Config::require('DB_USER');
        $pass    = (string) Config::require('DB_PASS');
        $charset = (string) Config::get('DB_CHARSET', 'utf8mb4');
        $port    = (int) Config::get('DB_PORT', 3306);

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);

        try {
            return new PDO(
                $dsn,
                $user,
                $pass,
                array(
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,    // real prepared statements
                    PDO::ATTR_PERSISTENT         => true,     // reuse the connection
                    PDO::ATTR_STRINGIFY_FETCHES  => false,
                )
            );
        } catch (PDOException $e) {
            Logger::error(
                'Database connection failed',
                array(
                    'host'  => $host,
                    'db'    => $name,
                    'error' => $e->getMessage(),
                )
            );
            throw $e;
        }
    }
}
