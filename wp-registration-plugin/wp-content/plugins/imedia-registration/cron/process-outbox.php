<?php

/**
 * IMedia Registration — cron entry point.
 *
 * Drains the outbox queue (up to 25 emails, 20s cap). Designed to be
 * invoked from cPanel > Cron Jobs:
 *
 *   /usr/local/bin/php /home/<account>/public_html/imedia-registration/cron/process-outbox.php
 *
 * Recommended cadence: every 5 minutes. The worker is idempotent and
 * safe to run concurrently with the admin "Process outbox now" button
 * (the FOR UPDATE locks serialize the row picks).
 *
 * Per php-pro: strict types, fail-loud, no echo (cPanel emails stderr
 * which is hard to read; errors go through the Logger).
 */

declare(strict_types=1);

require __DIR__ . '/../app/Core/Bootstrap.php';

use App\Services\OutboxWorker;

try {
    $result = OutboxWorker::run(25, 20);
    fwrite(STDOUT, sprintf(
        "outbox: sent=%d failed=%d requeued=%d remaining=%d elapsed_ms=%d\n",
        $result['sent'],
        $result['failed'],
        $result['requeued'],
        $result['remaining'],
        $result['elapsed_ms']
    ));
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, 'outbox cron failed: ' . $e->getMessage() . "\n");
    \App\Core\Logger::error('outbox.cron_exception', [
        'error' => $e->getMessage(),
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
    ]);
    exit(1);
}
