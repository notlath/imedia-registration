<?php
/**
 * Phase 5 regression smoke test (subprocess version).
 *
 * Spawns a child PHP process for each test, capturing the status
 * code + body. This is the same approach used in Phases 1-4.
 */

declare(strict_types=1);

$root = dirname(__DIR__);

$tests = [
    ['GET',  '/',                                   200, 'home stub'],
    ['GET',  '/admin/login',                        200, 'admin login form'],
    ['GET',  '/admin',                              302, 'admin dashboard without auth (redirect)'],
    ['GET',  '/admin/registrations',                302, 'registrations list without auth'],
    ['GET',  '/admin/registrations/1',              302, 'view reg without auth'],
    ['GET',  '/admin/registrations/1/resume',       302, 'resume dl without auth'],
    ['GET',  '/admin/outbox',                       302, 'outbox list without auth'],
    ['POST', '/admin/outbox/process',               302, 'outbox process without auth (also no csrf)'],
    ['POST', '/admin/outbox/1/retry',               302, 'outbox retry without auth'],
    ['POST', '/admin/registrations/1/delete',       302, 'delete without auth'],
    ['POST', '/admin/login',                        302, 'admin login POST redirects on CSRF fail'],
    ['GET',  '/api/submit',                         405, 'api/submit GET — only POST registered'],
    ['POST', '/api/submit',                         401, 'api/submit POST no HMAC'],
    ['GET',  '/nonexistent-route-12345',            404, '404 stub'],
];

$pass = 0;
$fail = 0;
$failures = [];

foreach ($tests as [$method, $path, $expected, $label]) {
    $tmpStatus = tempnam(sys_get_temp_dir(), 'p5status_');
    $tmpBody   = tempnam(sys_get_temp_dir(), 'p5body_');
    $driverFile = tempnam(sys_get_temp_dir(), 'p5drv_');
    // Write the driver as a PHP file with the parameters baked in.
    $driver = <<<PHP
<?php
declare(strict_types=1);
\$root   = __DIR__;
\$method = '$method';
\$path   = '$path';
\$statusFile = '$tmpStatus';
\$bodyFile   = '$tmpBody';
\$_SERVER['REQUEST_METHOD'] = \$method;
\$_SERVER['REQUEST_URI']    = \$path;
\$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
\$_SERVER['HTTP_HOST']       = 'test.local';
\$_SERVER['SERVER_NAME']     = 'test.local';
\$_SERVER['SERVER_PORT']     = 80;
\$_GET = []; \$_POST = []; \$_COOKIE = []; \$_FILES = [];
foreach (\$_SERVER as \$k => \$v) {
    if (is_string(\$k) && str_starts_with(\$k, 'HTTP_')) unset(\$_SERVER[\$k]);
}
ob_start();
require \$root . '/public/index.php';
\$b = ob_get_clean();
\$code = http_response_code();
if (\$code === false) \$code = 200;
file_put_contents(\$statusFile, (string) \$code);
file_put_contents(\$bodyFile, (string) \$b);
PHP;
    // Write driver into the plugin root so that __DIR__ resolves to the right place.
    $pluginRoot = $root;
    $tmpDriverInRoot = $pluginRoot . '/.p5drv_' . bin2hex(random_bytes(4)) . '.php';
    file_put_contents($tmpDriverInRoot, $driver);

    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmpDriverInRoot) . ' 2>&1';
    $output = shell_exec($cmd);

    @unlink($tmpDriverInRoot);
    $statusCode = (int) @file_get_contents($tmpStatus);
    $bodyShort  = substr((string) @file_get_contents($tmpBody), 0, 60);
    @unlink($tmpStatus);
    @unlink($tmpBody);

    if ($statusCode === $expected) {
        $pass++;
        fwrite(STDOUT, "  PASS  [$method $path] -> $statusCode ($label)\n");
    } else {
        $fail++;
        $msg = "  FAIL  [$method $path] expected=$expected got=$statusCode body=" . str_replace(["\n", "\r"], ' ', $bodyShort);
        if (!empty($output)) {
            $msg .= ' shell=' . str_replace(["\n", "\r"], ' ', substr((string) $output, 0, 80));
        }
        $failures[] = $msg;
        fwrite(STDOUT, $msg . "\n");
    }
}

fwrite(STDOUT, "\nSummary: $pass passed, $fail failed\n");
exit($fail === 0 ? 0 : 1);
