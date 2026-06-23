<?php

/**
 * IMedia Registration — Session wrapper.
 *
 * Centralizes every $_SESSION read/write in the codebase. The Router, the
 * Auth class, and the Csrf class all go through here. Phase 2 verification
 * greps the codebase for any raw $_SESSION reference outside this file.
 *
 * Per php-pro: strict types, final class, static-only API.
 */

declare(strict_types=1);

namespace App\Core;

final class Session {
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // In CLI (PHPUnit), PHP session functions warn after any output.
        // Bypass them entirely — just initialise $_SESSION directly.
        if (PHP_SAPI === 'cli') {
            if (! isset($_SESSION) || ! is_array($_SESSION)) {
                $_SESSION = array();
            }
            return;
        }

        $config = Bootstrap::loadConfig();

        session_name((string) ( $config['SESSION_NAME'] ?? 'imreg_session' ));

        $secure   = (bool) ( $config['SESSION_SECURE'] ?? true );
        $httpOnly = (bool) ( $config['SESSION_HTTPONLY'] ?? true );
        $sameSite = (string) ( $config['SESSION_SAMESITE'] ?? 'Lax' );

        session_set_cookie_params(
            array(
                'lifetime' => (int) ( $config['SESSION_LIFETIME'] ?? 7200 ),
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => $httpOnly,
                'samesite' => $sameSite,
            )
        );

        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', $httpOnly ? '1' : '0');
        ini_set('session.cookie_secure', $secure ? '1' : '0');
        ini_set('session.cookie_samesite', $sameSite);

        session_start();
    }

    public static function get( string $key, mixed $default = null ): mixed {
        self::start();
        return $_SESSION[ $key ] ?? $default;
    }

    public static function put( string $key, mixed $value ): void {
        self::start();
        $_SESSION[ $key ] = $value;
    }

    public static function forget( string $key ): void {
        self::start();
        unset($_SESSION[ $key ]);
    }

    /**
     * Stash a one-shot message under the given flash key.
     * Accepts any scalar or array value; callers use type-appropriate
     * helpers (View::old, View::errors) to retrieve it.
     */
    public static function flash( string $key, mixed $message ): void {
        self::start();
        if (! isset($_SESSION['_flash']) || ! is_array($_SESSION['_flash'])) {
            $_SESSION['_flash'] = array();
        }
        $_SESSION['_flash'][ $key ] = $message;
    }

    /**
     * Return the flash message and remove it.
     */
    public static function pullFlash( string $key ): mixed {
        self::start();
        if (! isset($_SESSION['_flash'][ $key ])) {
            return null;
        }
        $msg = $_SESSION['_flash'][ $key ];
        unset($_SESSION['_flash'][ $key ]);
        return $msg;
    }

    /**
     * Regenerate the session ID. Call after a successful login to prevent
     * session fixation (php-pro).
     */
    public static function regenerate(): void
    {
        self::start();
        if (PHP_SAPI === 'cli') {
            return;
        }
        session_regenerate_id(true);
    }
    /**
     * Destroy the entire session. Used for logout.
     */
    public static function destroy(): void {
        self::start();
        $_SESSION = array();
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                array(
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                )
            );
        }
        session_destroy();
    }
}
