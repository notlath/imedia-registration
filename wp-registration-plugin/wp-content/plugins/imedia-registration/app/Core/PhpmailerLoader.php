<?php

/**
 * IMedia Registration — PHPMailer loader.
 *
 * Loads the vendored PHPMailer 6.x source files. We do this manually
 * (rather than via Composer) because the plugin folder ships without
 * a dependency manager. The class names, namespaces, and method
 * signatures match upstream PHPMailer 6.x — so this directory can
 * be replaced with a real `vendor/phpmailer/phpmailer` install by
 * deleting this loader and pointing PSR-4 at the right path.
 *
 * Per php-pro: strict types, idempotent (safe to call multiple times).
 */

declare(strict_types=1);

namespace App\Core;

final class PhpmailerLoader
{
    private static bool $loaded = false;

    /**
     * Load the three vendored PHPMailer files. Idempotent.
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }
        $base = IMREG_BASE_PATH . '/vendor/PHPMailer/src';
        if (!is_file($base . '/Exception.php')) {
            throw new \RuntimeException('PHPMailer vendor missing at ' . $base);
        }
        require_once $base . '/Exception.php';
        require_once $base . '/SMTP.php';
        require_once $base . '/PHPMailer.php';
        self::$loaded = true;
    }
}
