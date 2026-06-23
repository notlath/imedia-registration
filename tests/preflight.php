<?php
declare(strict_types=1);

$hasPcov = extension_loaded('pcov');
$hasXdebug = extension_loaded('xdebug')
    && (function_exists('xdebug_set_filter') || phpversion('xdebug') >= '2.9');

if ($hasPcov) {
    fwrite(STDOUT, "[preflight] Coverage driver: PCOV\n");
    exit(0);
}
if ($hasXdebug) {
    fwrite(STDOUT, "[preflight] Coverage driver: Xdebug\n");
    exit(0);
}

fwrite(STDERR, "[preflight] WARNING: No coverage driver found (PCOV or Xdebug).\n");
fwrite(STDERR, "[preflight] Tests will run without coverage reporting.\n");
fwrite(STDERR, "[preflight] Install php-pcov or php-xdebug to enable coverage.\n");
fwrite(STDERR, "[preflight]   sudo apt install php-pcov\n");
fwrite(STDERR, "[preflight]   sudo pecl install pcov\n");
fwrite(STDERR, "[preflight] Continuing without coverage.\n");
exit(0);
