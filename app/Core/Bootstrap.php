<?php

/**
 * IMedia Registration — Bootstrap.
 *
 * Loaded by public/index.php (and any other entry point that needs the app).
 * Responsibilities:
 *   - Define base paths.
 *   - Load the typed config array.
 *   - Register a PSR-4 autoloader for the App\ namespace.
 *   - Set secure defaults (error reporting, timezone, headers).
 *   - Start the session.
 *
 * Per php-pro skill:
 *   - declare(strict_types=1)
 *   - typed properties / readonly where applicable
 *   - no global mutable state (Config is a static read-only registry)
 */

declare(strict_types=1);

namespace App\Core;

final class Bootstrap {
    private static bool $booted = false;

    /**
     * Run the bootstrap once. Idempotent.
     */
    public static function init(): void {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        // ---- Paths ----
        // __DIR__ is app/Core/, so the plugin root is two levels up.
        if (! defined('IMREG_BASE_PATH')) {
            define('IMREG_BASE_PATH', dirname(__DIR__, 2));
        }
        if (! defined('IMREG_APP_PATH')) {
            define('IMREG_APP_PATH', IMREG_BASE_PATH . '/app');
        }
        if (! defined('IMREG_PUBLIC_PATH')) {
            define('IMREG_PUBLIC_PATH', IMREG_BASE_PATH . '/public');
        }
        if (! defined('IMREG_VIEWS_PATH')) {
            define('IMREG_VIEWS_PATH', IMREG_BASE_PATH . '/resources/views');
        }
        if (! defined('IMREG_CONFIG_PATH')) {
            define('IMREG_CONFIG_PATH', IMREG_BASE_PATH . '/config');
        }
        if (! defined('IMREG_STORAGE_PATH')) {
            define('IMREG_STORAGE_PATH', IMREG_BASE_PATH . '/storage');
        }

        // ---- Error reporting ----
        $config = self::loadConfig();
        $isDebug = (bool) ( $config['APP_DEBUG'] ?? false );
        ini_set('display_errors', $isDebug ? '1' : '0');
        ini_set('display_startup_errors', $isDebug ? '1' : '0');
        error_reporting($isDebug ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_STRICT);

        // ---- Timezone ----
        date_default_timezone_set('UTC');

        // ---- Autoloader (PSR-4: App\ => app/) ----
        spl_autoload_register(
            static function ( string $class ): void {
                $prefix = 'App\\';
                if (! str_starts_with($class, $prefix)) {
                    return;
                }
                $relative = substr($class, strlen($prefix));
                $path = IMREG_APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
                if (is_file($path)) {
                    require_once $path;
                }
            }
        );

        // ---- Session is started lazily by Session::start() the first
        // time something actually needs $_SESSION. That keeps the public
        // submit flow session-free (no Set-Cookie on visitor pages).
        if (PHP_SAPI !== 'cli') {
            self::sendSecurityHeaders();
        }
    }

    /**
     * Load config.php if present, else fall back to config.example.php.
     * Returns a typed array.
     */
    public static function loadConfig(): array {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $configFile = IMREG_CONFIG_PATH . '/config.php';
        if (! is_file($configFile)) {
            // Fallback for first-run / smoke-test scenarios
            $configFile = IMREG_CONFIG_PATH . '/config.example.php';
        }

        /** @var array $config */
        $config = require $configFile;
        if (! is_array($config)) {
            throw new \RuntimeException('config.php must return an array');
        }
        $cached = $config;
        return $cached;
    }

    /**
     * Send baseline HTTP security headers.
     */
    private static function sendSecurityHeaders(): void {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
}
