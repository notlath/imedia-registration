<?php

/**
 * IMedia Registration — typed config accessor.
 *
 * The config array is loaded once by Bootstrap::loadConfig() from
 * config/config.php (or config.example.php as a fallback). This class is
 * the single typed entry point for reading config values.
 *
 * Per php-pro: strict types, final class, static-only API. No globals
 * leak beyond this small static registry.
 */

declare(strict_types=1);

namespace App\Core;

final class Config {
    /**
     * Return the raw config array (read-only — callers must not mutate it).
     *
     * @return array<string, mixed>
     */
    public static function all(): array {
        return Bootstrap::loadConfig();
    }

    /**
     * Get a config value, or a default if missing.
     */
    public static function get( string $key, mixed $default = null ): mixed {
        $data = self::all();
        return array_key_exists($key, $data) ? $data[ $key ] : $default;
    }

    /**
     * Get a required key. Throws if missing.
     */
    public static function require( string $key ): mixed {
        $value = self::get($key);
        if ($value === null) {
            throw new \RuntimeException("Required config key missing: {$key}");
        }
        return $value;
    }
}
