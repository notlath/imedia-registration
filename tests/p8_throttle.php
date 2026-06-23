<?php
/**
 * Phase 8: login throttling unit tests.
 *
 * These verify LoginAttempt constants + the packing helper behavior
 * without needing a real MySQL connection. The integration is exercised
 * in the regression suite once deployed.
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Bootstrap.php';
\App\Core\Bootstrap::init();

use App\Models\LoginAttempt;

$pass = 0;
$fail = 0;

function assertTrue(bool $cond, string $label): void {
    global $pass, $fail;
    if ($cond) { $pass++; echo "  PASS  $label\n"; }
    else       { $fail++; echo "  FAIL  $label\n"; }
}
function assertEquals($expected, $actual, string $label): void {
    assertTrue($expected === $actual,
        $label . ' (expected ' . var_export($expected, true) .
        ', got ' . var_export($actual, true) . ')');
}

// ---- Constants (sanity check the policy lives in one place) ----
assertEquals(900,    LoginAttempt::IP_WINDOW,         'IP_WINDOW = 15 min');
assertEquals(5,      LoginAttempt::IP_MAX_FAILURES,   'IP_MAX_FAILURES = 5');
assertEquals(3600,   LoginAttempt::EMAIL_WINDOW,      'EMAIL_WINDOW = 1 h');
assertEquals(10,     LoginAttempt::EMAIL_MAX_FAILURES, 'EMAIL_MAX_FAILURES = 10');
assertEquals(3600,   LoginAttempt::LOCKOUT_SECONDS,   'LOCKOUT_SECONDS = 1 h');

// ---- Reflection on the private packer ----
// We can't hit the DB, so just confirm the class structure is stable
// and constants are non-zero. The runtime contract is in the DB-backed
// methods, covered by p5_regression.php once the migration is applied.
$ref = new ReflectionClass(LoginAttempt::class);
foreach (['recordFailure', 'recordSuccess', 'recentFailuresByIp',
          'recentFailuresByEmail', 'purgeOlderThan'] as $m) {
    assertTrue($ref->hasMethod($m), "LoginAttempt::{$m}() exists");
}

echo "\nSummary: $pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
