<?php

/**
 * IMedia Registration — file logger.
 *
 * One log file per day under storage/logs/app-YYYY-MM-DD.log. Lines are
 * line-buffered. Suitable for a cPanel app without psr/log.
 */

declare(strict_types=1);

namespace App\Core;

final class Logger {
    private const LEVELS = array(
        'debug'   => 0,
        'info'    => 1,
        'warning' => 2,
        'error'   => 3,
    );

    public static function info( string $message, array $context = array() ): void {
        self::write('info', $message, $context);
    }

    public static function warning( string $message, array $context = array() ): void {
        self::write('warning', $message, $context);
    }

    public static function error( string $message, array $context = array() ): void {
        self::write('error', $message, $context);
    }

    private static function write( string $level, string $message, array $context ): void {
        $configured = (string) Config::get('LOG_LEVEL', 'info');
        $threshold  = self::LEVELS[ $configured ] ?? 1;
        if (self::LEVELS[ $level ] < $threshold) {
            return;
        }

        $path = (string) Config::get('LOG_PATH', IMREG_STORAGE_PATH . '/logs/app.log');
        // If the configured path is a directory or a non-dated file, roll by day.
        if (! str_ends_with($path, '.log')) {
            $path = rtrim($path, '/\\') . '/app-' . date('Y-m-d') . '.log';
        }

        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $line = sprintf(
            "[%s] [%s] %s%s\n",
            date('c'),
            strtoupper($level),
            $message,
            $context !== array() ? ' | ' . self::stringify($context) : ''
        );

        // LOCK_EX prevents interleaved writes under concurrency.
        @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }

    private static function stringify( array $context ): string {
        $out = array();
        foreach ($context as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $out[] = $k . '=' . ( is_string($v) ? '"' . addslashes($v) . '"' : var_export($v, true) );
            } else {
                $out[] = $k . '=' . json_encode($v, JSON_UNESCAPED_SLASHES);
            }
        }
        return implode(' ', $out);
    }
}
