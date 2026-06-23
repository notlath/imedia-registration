<?php

/**
 * IMedia Registration — Request value object.
 *
 * Per php-pro: readonly value object built once from PHP super-globals.
 * Parsing priority (per common REST conventions):
 *   1. JSON body  — if Content-Type starts with "application/json".
 *   2. Form body  — if Content-Type starts with "application/x-www-form-urlencoded".
 *   3. Multipart  — if Content-Type starts with "multipart/form-data".
 *   4. Fallback   — empty body.
 *
 * Files (multipart only) are flattened into a single map:
 *   $req->file('resume') => ['name'=>'...', 'tmp_name'=>'...', 'size'=>..., 'error'=>..., 'type'=>...]
 */

declare(strict_types=1);

namespace App\Core;

final readonly class Request {
    /**
     * @param array<string, mixed>  $query
     * @param array<string, mixed>  $body
     * @param array<string, array>  $files
     * @param array<string, string> $headers
     * @param array<string, mixed>  $params  path params from the Router
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query,
        public array $body,
        public array $files,
        public array $headers,
        public array $params = array(),
    ) {
    }

    /**
     * Build a Request from PHP super-globals.
     */
    public static function fromGlobals(): self {
        $method  = strtoupper((string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ));
        $uri     = (string) ( $_SERVER['REQUEST_URI'] ?? '/' );
        $path    = parse_url($uri, PHP_URL_PATH) ?: '/';
        $query   = array();
        $qString = parse_url($uri, PHP_URL_QUERY);
        if (is_string($qString) && $qString !== '') {
            parse_str($qString, $query);
        }

        // Headers (case-insensitive lookup map)
        $headers = self::collectHeaders();

        $contentType = strtolower($headers['content-type'] ?? '');

        // Body parsing
        $body  = array();
        $files = array();

        if (str_starts_with($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $body = $decoded;
                }
            }
        } elseif (str_starts_with($contentType, 'multipart/form-data')) {
            $body  = $_POST;
            $files = self::flattenFiles($_FILES);
        } else {
            // application/x-www-form-urlencoded or other form types
            $body = $_POST;
        }

        return new self($method, $path, $query, $body, $files, $headers);
    }

    public function header( string $name ): ?string {
        $key = strtolower($name);
        return $this->headers[ $key ] ?? null;
    }

    public function query( string $key, mixed $default = null ): mixed {
        return $this->query[ $key ] ?? $default;
    }

    public function input( string $key, mixed $default = null ): mixed {
        return $this->body[ $key ] ?? $default;
    }

    public function file( string $key ): ?array {
        return $this->files[ $key ] ?? null;
    }

    public function param( string $key, mixed $default = null ): mixed {
        return $this->params[ $key ] ?? $default;
    }

    public function ip(): string {
        return (string) ( $_SERVER['REMOTE_ADDR'] ?? '' );
    }

    /**
     * Return the raw request body as a string. Useful for HMAC verification
     * because we sign the exact bytes that were POSTed.
     */
    public function rawBody(): string {
        $body = file_get_contents('php://input');
        return is_string($body) ? $body : '';
    }

    /**
     * @return array<string, string>
     */
    private static function collectHeaders(): array {
        if (function_exists('getallheaders')) {
            $all = getallheaders();
            if (is_array($all)) {
                $out = array();
                foreach ($all as $name => $value) {
                    $out[ strtolower((string) $name) ] = (string) $value;
                }
                return $out;
            }
        }
        // Fallback: scan $_SERVER for HTTP_* keys
        $out = array();
        foreach ($_SERVER as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $out[ $name ] = (string) $value;
            }
        }
        return $out;
    }

    /**
     * Flatten PHP's nested $_FILES into a per-field map.
     *
     * @return array<string, array{name: string, tmp_name: string, size: int, error: int, type: string}>
     */
    private static function flattenFiles( array $files ): array {
        $out = array();
        foreach ($files as $field => $data) {
            if (! is_array($data) || ! isset($data['name'])) {
                continue;
            }
            if (is_array($data['name'])) {
                // field[] — pick the first file (or skip; v1 doesn't support arrays of files)
                continue;
            }
            $out[ $field ] = array(
                'name'     => (string) $data['name'],
                'tmp_name' => (string) $data['tmp_name'],
                'size'     => (int) $data['size'],
                'error'    => (int) $data['error'],
                'type'     => (string) ( $data['type'] ?? '' ),
            );
        }
        return $out;
    }
}
