<?php
/**
 * Phase 7 — HMAC + replay-protection unit tests.
 *
 * Pre-declares App\Core\Request and App\Models\Setting as test
 * doubles BEFORE the autoloader registers. The autoloader's
 * require_once is a no-op when the class is already declared,
 * so our doubles shadow the real ones for the duration of this
 * script.
 *
 * The doubles expose static fields for the test to set:
 *   Request::$testBody (string) — returned by rawBody()
 *   Request::$testBodyParsed (array) — returned by body field
 *   Setting::$testSecret (string) — returned by get('hmac_shared_secret')
 *
 * Run with: php tests/p7_unit.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);

// ----- Test doubles (declared before any require of Bootstrap) -----

eval(<<<'PHP'
namespace App\Core;

final class Request
{
    public static string $testBody = '';
    public static array  $testBodyParsed = [];
    public static array  $testHeaders = [];
    public static string $testPath = '/api/submit';
    public string $method = 'POST';
    public string $path = '/api/submit';
    public array  $body = [];
    public array  $headers = [];
    public array  $query = [];
    public array  $files = [];
    public array  $params = [];
    public function __construct() {}
    public function header(string $name): ?string {
        return $this->headers[strtolower($name)] ?? null;
    }
    public function query(string $k, mixed $d = null): mixed { return $this->query[$k] ?? $d; }
    public function input(string $k, mixed $d = null): mixed { return $this->body[$k] ?? $d; }
    public function file(string $k): ?array { return $this->files[$k] ?? null; }
    public function param(string $k, mixed $d = null): mixed { return $this->params[$k] ?? $d; }
    public function rawBody(): string { return self::$testBody; }
    public function ip(): string { return '127.0.0.1'; }
    public function userAgent(): string { return 'p7-unit'; }
    public function expectsJson(): bool { return false; }
}

namespace App\Models;

final class Setting
{
    public static string $testSecret = '';
    public static function get(string $key, mixed $default = null): mixed {
        if ($key === 'hmac_shared_secret') return self::$testSecret;
        return $default;
    }
    public static function put(string $key, mixed $value): void {}
    public static function refresh(): array { return []; }
    public static function all(): array { return []; }
}
PHP);

// Suppress the autoloader for these two classes by pre-declaring
// them (done above). The autoloader's require_once is a no-op on
// an already-loaded class.
require $root . '/app/Core/Bootstrap.php';
\App\Core\Bootstrap::init();

use App\Core\{Hmac, Response};
use App\Middleware\HmacVerify;

// ----- Test harness -----

$secret = 'p7-shared-secret-' . bin2hex(random_bytes(4));
\App\Models\Setting::$testSecret = $secret;

$now = time();
$pass = 0;
$fail = 0;

function callMiddleware(string $bodyStr, array $parsedBody, array $headers): array {
    global $now;
    \App\Core\Request::$testBody = $bodyStr;
    \App\Core\Request::$testBodyParsed = $parsedBody;
    \App\Core\Request::$testHeaders = $headers;

    // Construct a Request via reflection (Request has a private
    // constructor in the real code; in the test double we made
    // it public).
    $req = (function () {
        $r = new \App\Core\Request();
        return $r;
    })();
    // Override the request's parsed body + headers via reflection
    // (they are public in the test double anyway, so direct access works).
    $req->body = \App\Core\Request::$testBodyParsed;
    $req->headers = \App\Core\Request::$testHeaders;

    $res = \App\Core\Response::make();
    $mw  = new \App\Middleware\HmacVerify();
    $out = $mw($req, $res, static fn ($r, $res) => $res->json(['ok' => true], 200));
    return ['status' => $out->getStatus(), 'body' => $out->getBody()];
}

$scenarios = [
    'no header' => [
        'bodyStr'    => json_encode(['_imf_form_id' => 1, '_imf_timestamp' => $now]),
        'parsedBody' => ['_imf_form_id' => 1, '_imf_timestamp' => $now],
        'headers'    => [],
        'expect'     => 401,
    ],
    'malformed header (no sha256=)' => [
        'bodyStr'    => json_encode(['_imf_form_id' => 1, '_imf_timestamp' => $now]),
        'parsedBody' => ['_imf_form_id' => 1, '_imf_timestamp' => $now],
        'headers'    => ['x-imf-signature' => 'not-a-signature'],
        'expect'     => 401,
    ],
    'tampered signature' => [
        'bodyStr'    => json_encode(['_imf_form_id' => 1, '_imf_timestamp' => $now, 'name' => 'Tampered']),
        'parsedBody' => ['_imf_form_id' => 1, '_imf_timestamp' => $now, 'name' => 'Tampered'],
        'headers'    => ['x-imf-signature' => 'sha256=deadbeefcafebabe'],
        'expect'     => 401,
    ],
    'valid signature, no timestamp' => [
        'bodyStr'    => json_encode(['_imf_form_id' => 1, 'name' => 'NoTs']),
        'parsedBody' => ['_imf_form_id' => 1, 'name' => 'NoTs'],
        'headers'    => [], // will be set below after computing sig
        'expect'     => 401,
    ],
    'valid signature, stale timestamp (-1h)' => [
        'bodyStr'    => json_encode(['_imf_form_id' => 1, '_imf_timestamp' => $now - 3600]),
        'parsedBody' => ['_imf_form_id' => 1, '_imf_timestamp' => $now - 3600],
        'headers'    => [],
        'expect'     => 401,
    ],
    'valid signature, future timestamp (+1h)' => [
        'bodyStr'    => json_encode(['_imf_form_id' => 1, '_imf_timestamp' => $now + 3600]),
        'parsedBody' => ['_imf_form_id' => 1, '_imf_timestamp' => $now + 3600],
        'headers'    => [],
        'expect'     => 401,
    ],
    'valid signature, fresh timestamp (handler runs)' => [
        'bodyStr'    => json_encode(['_imf_form_id' => 1, '_imf_timestamp' => $now]),
        'parsedBody' => ['_imf_form_id' => 1, '_imf_timestamp' => $now],
        'headers'    => [],
        'expect'     => 200, // the next() closure returns 200
    ],
    'valid signature, non-numeric timestamp' => [
        'bodyStr'    => json_encode(['_imf_form_id' => 1, '_imf_timestamp' => 'yesterday']),
        'parsedBody' => ['_imf_form_id' => 1, '_imf_timestamp' => 'yesterday'],
        'headers'    => [],
        'expect'     => 401,
    ],
];

// Compute valid signatures for the "valid sig" scenarios.
foreach (['valid signature, no timestamp', 'valid signature, stale timestamp (-1h)',
          'valid signature, future timestamp (+1h)', 'valid signature, fresh timestamp (handler runs)',
          'valid signature, non-numeric timestamp'] as $k) {
    $body = $scenarios[$k]['bodyStr'];
    $scenarios[$k]['headers'] = ['x-imf-signature' => 'sha256=' . hash_hmac('sha256', $body, $secret)];
}

foreach ($scenarios as $label => $s) {
    $r = callMiddleware($s['bodyStr'], $s['parsedBody'], $s['headers']);
    $exp = $s['expect'];
    if ($r['status'] === $exp) {
        $pass++;
        fwrite(STDOUT, "  PASS  [{$label}] -> {$r['status']} (expected {$exp})\n");
    } else {
        $fail++;
        fwrite(STDOUT, "  FAIL  [{$label}] expected {$exp} got {$r['status']} body=" . substr($r['body'], 0, 80) . "\n");
    }
}

fwrite(STDOUT, "\nSummary: {$pass} passed, {$fail} failed\n");
exit($fail === 0 ? 0 : 1);
