<?php

/**
 * IMedia Registration — OutboxEmail model.
 *
 * Phase 5 wires PHPMailer and a worker (cron or admin-triggered) to
 * actually send these. The worker calls claim() inside a transaction
 * with FOR UPDATE row locks, then per-row calls markSent() or
 * markFailed(). The controller view layer uses listByStatus() and
 * count() (Phase 3) plus retry() (Phase 5).
 *
 * Per php-pro: strict types, named placeholders, typed return values.
 * Per mysql skill: every query is parameterized; FOR UPDATE used only
 *   inside the worker's transaction.
 * Per database-optimizer: covering index (status, queued_at) reused.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class OutboxEmail {
    public const STATUS_QUEUED  = 'queued';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';

    /**
     * Enqueue an email. Returns the new outbox id.
     *
     * @param array<string, mixed> $context Structured payload for the worker
     */
    public static function enqueue(
        string $toEmail,
        string $subject,
        string $bodyHtml,
        array $context = array()
    ): int {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO outbox_emails (to_email, subject, body_html, context)
             VALUES (:to, :subj, :body, :ctx)'
        );
        $stmt->execute(
            array(
                ':to'   => $toEmail,
                ':subj' => $subject,
                ':body' => $bodyHtml,
                ':ctx'  => $context === array() ? null : json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            )
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * @return array<int, array{
     *   id:int, to_email:string, subject:string,
     *   status:string, attempts:int, last_error:?string, context:?string,
     *   queued_at:string, sent_at:?string
     * }>
     */
    public static function listByStatus( string $status, int $limit = 50 ): array {
        $stmt = Database::pdo()->prepare(
            'SELECT id, to_email, subject, status, attempts, last_error,
                    context, queued_at, sent_at
             FROM outbox_emails
             WHERE status = :s
             ORDER BY queued_at DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':s', $status);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $out = array();
        foreach ($rows as $row) {
            $out[] = array(
                'id'         => (int) $row['id'],
                'to_email'   => (string) $row['to_email'],
                'subject'    => (string) $row['subject'],
                'status'     => (string) $row['status'],
                'attempts'   => (int) $row['attempts'],
                'last_error' => $row['last_error'] === null ? null : (string) $row['last_error'],
                'context'    => $row['context'] === null ? null : (string) $row['context'],
                'queued_at'  => (string) $row['queued_at'],
                'sent_at'    => $row['sent_at'] === null ? null : (string) $row['sent_at'],
            );
        }
        return $out;
    }

    public static function count( string $status ): int {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM outbox_emails WHERE status = :s');
        $stmt->execute(array( ':s' => $status ));
        return (int) $stmt->fetchColumn();
    }

    /**
     * One round-trip count for the three outbox tabs. Caller passes the
     * status values it cares about; missing keys default to 0.
     *
     * @param array<int, string> $statuses
     * @return array<string, int>
     */
    public static function countsByStatus( array $statuses ): array {
        if ($statuses === array()) {
            return array();
        }
        $placeholders = array();
        $bind         = array();
        foreach (array_values($statuses) as $i => $s) {
            $key          = ':s' . $i;
            $placeholders[] = $key;
            $bind[ $key ]   = $s;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT status, COUNT(*) AS c FROM outbox_emails
             WHERE status IN (' . implode(',', $placeholders) . ')
             GROUP BY status'
        );
        $stmt->execute($bind);
        $out = array_fill_keys($statuses, 0);
        foreach ($stmt->fetchAll() as $row) {
            $out[ (string) $row['status'] ] = (int) $row['c'];
        }
        return $out;
    }

    /**
     * Claim up to $limit queued rows with FOR UPDATE locks. The caller
     * must have an open transaction on $pdo (or pass the singleton's
     * pdo() and a transaction will be started). Returns the row array
     * exactly as listByStatus() does.
     *
     * @return array<int, array{
     *   id:int, to_email:string, subject:string, body_html:string,
     *   status:string, attempts:int, last_error:?string, context:?string,
     *   queued_at:string, sent_at:?string
     * }>
     */
    public static function claim( int $limit, ?\PDO $pdo = null ): array {
        $pdo  = $pdo ?? Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT id, to_email, subject, body_html, status, attempts, last_error,
                    context, queued_at, sent_at
             FROM outbox_emails
             WHERE status = :s
             ORDER BY queued_at ASC
             LIMIT :lim
             FOR UPDATE'
        );
        $stmt->bindValue(':s', self::STATUS_QUEUED);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $out = array();
        foreach ($rows as $row) {
            $out[] = array(
                'id'         => (int) $row['id'],
                'to_email'   => (string) $row['to_email'],
                'subject'    => (string) $row['subject'],
                'body_html'  => (string) $row['body_html'],
                'status'     => (string) $row['status'],
                'attempts'   => (int) $row['attempts'],
                'last_error' => $row['last_error'] === null ? null : (string) $row['last_error'],
                'context'    => $row['context'] === null ? null : (string) $row['context'],
                'queued_at'  => (string) $row['queued_at'],
                'sent_at'    => $row['sent_at'] === null ? null : (string) $row['sent_at'],
            );
        }
        return $out;
    }

    /**
     * Mark a single row as sent. Increments attempts.
     */
    public static function markSent( int $id, ?\PDO $pdo = null ): void {
        $pdo = $pdo ?? Database::pdo();
        $stmt = $pdo->prepare(
            "UPDATE outbox_emails
             SET status = 'sent', sent_at = NOW(), attempts = attempts + 1,
                 last_error = NULL
             WHERE id = :id"
        );
        $stmt->execute(array( ':id' => $id ));
    }

    /**
     * Mark a single row as failed.
     *
     * @param bool $terminal true → status='failed' (give up); false → status stays 'queued',
     *                       attempts just bumps (will be retried).
     */
    public static function markFailed( int $id, string $error, bool $terminal, ?\PDO $pdo = null ): void {
        $pdo = $pdo ?? Database::pdo();
        $error = mb_substr($error, 0, 4000);
        if ($terminal) {
            $stmt = $pdo->prepare(
                "UPDATE outbox_emails
                 SET status = 'failed', sent_at = NOW(), attempts = attempts + 1,
                     last_error = :err
                 WHERE id = :id"
            );
        } else {
            $stmt = $pdo->prepare(
                'UPDATE outbox_emails
                 SET attempts = attempts + 1, last_error = :err
                 WHERE id = :id'
            );
        }
        $stmt->execute(
            array(
                ':id' => $id,
                ':err' => $error,
            )
        );
    }

    /**
     * Reset a failed row back to queued. The admin uses this from the
     * Outbox UI to re-queue an email after fixing the SMTP issue.
     */
    public static function retry( int $id ): bool {
        $stmt = Database::pdo()->prepare(
            "UPDATE outbox_emails
             SET status = 'queued', attempts = 0, last_error = NULL, sent_at = NULL
             WHERE id = :id AND status = 'failed'"
        );
        $stmt->execute(array( ':id' => $id ));
        return $stmt->rowCount() > 0;
    }

    /**
     * Convenience for the worker: how many queued rows remain after a run.
     */
    public static function remaining(): int {
        return self::count(self::STATUS_QUEUED);
    }
}
