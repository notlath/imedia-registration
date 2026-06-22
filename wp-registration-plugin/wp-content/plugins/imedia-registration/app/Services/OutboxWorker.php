<?php

/**
 * IMedia Registration — Outbox worker.
 *
 * Drains up to $batchSize queued rows in one run, capped at
 * $maxSeconds wall-clock time. Each row is sent via Mailer.
 *   - success     → markSent
 *   - attempts<3  → requeue (just bump attempts + last_error)
 *   - attempts==3 → markFailed (status='failed', attempts==3, last_error set)
 *
 * Per php-pro: strict types, readonly where applicable.
 * Per mysql skill: one transaction with SELECT ... FOR UPDATE locks;
 *   deadlocks (errno 1213) are retried once.
 * Per database-optimizer: covering index (status, queued_at) reused.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, Logger};
use App\Models\OutboxEmail;

final class OutboxWorker
{
    /**
     * @return array{sent:int, failed:int, requeued:int, remaining:int, started:float, elapsed_ms:int}
     */
    public static function run(int $batchSize = 25, int $maxSeconds = 20): array
    {
        $started = microtime(true);
        // Give ourselves 2s headroom over the per-row time limit so the
        // script can log + commit even if the last SMTP attempt hits
        // its own timeout.
        @set_time_limit($maxSeconds + 2);

        $sent = 0; $failed = 0; $requeued = 0;
        $deadline = $started + $maxSeconds;

        $pdo = Database::pdo();
        $attempt = 0;
        $maxAttempts = 2; // outer retry on deadlock
        while ($attempt < $maxAttempts) {
            $attempt++;
            try {
                $pdo->beginTransaction();
                $rows = OutboxEmail::claim($batchSize, $pdo);
                if ($rows === []) {
                    $pdo->commit();
                    break;
                }
                foreach ($rows as $row) {
                    // Wall-clock cap: stop mid-batch if we're past the deadline.
                    if (microtime(true) >= $deadline) {
                        Logger::info('outbox.time_cap_hit', [
                            'sent_so_far' => $sent,
                            'batch'       => $batchSize,
                        ]);
                        break 2;
                    }

                    $error = null;
                    $ok    = Mailer::send(
                        $row['to_email'],
                        $row['subject'],
                        $row['body_html'],
                        $error
                    );
                    if ($ok) {
                        OutboxEmail::markSent((int) $row['id'], $pdo);
                        $sent++;
                    } else {
                        $attempts = (int) $row['attempts'] + 1;
                        $terminal = $attempts >= Mailer::MAX_ATTEMPTS;
                        OutboxEmail::markFailed((int) $row['id'], $error ?? 'unknown', $terminal, $pdo);
                        if ($terminal) {
                            $failed++;
                        } else {
                            $requeued++;
                        }
                    }
                }
                $pdo->commit();
                break; // success
            } catch (\PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                // MySQL deadlock (1213) or lock-wait timeout (1205) — retry once.
                $code = (int) $e->getCode();
                if (in_array($code, [1213, 1205], true) && $attempt < $maxAttempts) {
                    Logger::warning('outbox.deadlock_retry', [
                        'code'  => $code,
                        'error' => $e->getMessage(),
                    ]);
                    usleep(50_000); // 50ms backoff
                    continue;
                }
                Logger::error('outbox.worker_exception', [
                    'code'  => $code,
                    'error' => $e->getMessage(),
                ]);
                return [
                    'sent'        => $sent,
                    'failed'      => $failed,
                    'requeued'    => $requeued,
                    'remaining'   => OutboxEmail::remaining(),
                    'started'     => $started,
                    'elapsed_ms'  => (int) round((microtime(true) - $started) * 1000),
                ];
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                Logger::error('outbox.worker_exception', [
                    'error' => $e->getMessage(),
                ]);
                return [
                    'sent'        => $sent,
                    'failed'      => $failed,
                    'requeued'    => $requeued,
                    'remaining'   => OutboxEmail::remaining(),
                    'started'     => $started,
                    'elapsed_ms'  => (int) round((microtime(true) - $started) * 1000),
                ];
            }
        }

        $remaining = OutboxEmail::remaining();
        $elapsed   = (int) round((microtime(true) - $started) * 1000);
        Logger::info('outbox.run_complete', [
            'sent'      => $sent,
            'failed'    => $failed,
            'requeued'  => $requeued,
            'remaining' => $remaining,
            'elapsed_ms'=> $elapsed,
        ]);
        return [
            'sent'        => $sent,
            'failed'      => $failed,
            'requeued'    => $requeued,
            'remaining'   => $remaining,
            'started'     => $started,
            'elapsed_ms'  => $elapsed,
        ];
    }
}
