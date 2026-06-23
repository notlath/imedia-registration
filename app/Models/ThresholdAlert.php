<?php

/**
 * IMedia Registration — ThresholdAlert model.
 *
 * The threshold_alerts_sent table has a UNIQUE KEY on (course, year, month)
 * to prevent duplicate alert emails. recordAndAlert() returns true the
 * first time a slot hits the threshold, false on subsequent attempts.
 *
 * Per mysql skill: relies on the unique key for race-condition safety.
 * The INSERT IGNORE pattern + the explicit error-code check is belt-and-
 * suspenders.
 *
 * Per php-pro: strict types, typed returns.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

final class ThresholdAlert {
    /**
     * Record that an alert was sent for the given (course, year, month) slot.
     * Returns true if this is the first time (i.e. the alert is "fresh"),
     * false if the slot was already alerted.
     */
    public static function recordAndAlert( string $course, int $year, int $month ): bool {
        // First, the cheap check: is the slot already alerted?
        if (self::wasAlerted($course, $year, $month)) {
            return false;
        }

        try {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO threshold_alerts_sent (course, course_year, course_month)
                 VALUES (:c, :y, :m)'
            );
            $stmt->execute(
                array(
                    ':c' => $course,
                    ':y' => $year,
                    ':m' => $month,
                )
            );
            return true;
        } catch (PDOException $e) {
            // 1062 = duplicate key. Race condition: another request inserted
            // the same slot in the gap between our SELECT and INSERT.
            if ((string) ( $e->errorInfo[1] ?? '' ) === '1062') {
                return false;
            }
            throw $e;
        }
    }

    public static function wasAlerted( string $course, int $year, int $month ): bool {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM threshold_alerts_sent
             WHERE course = :c AND course_year = :y AND course_month = :m
             LIMIT 1'
        );
        $stmt->execute(
            array(
                ':c' => $course,
                ':y' => $year,
                ':m' => $month,
            )
        );
        return (bool) $stmt->fetchColumn();
    }

    /**
     * @return array<int, array{course:string, course_year:int, course_month:int, sent_at:string}>
     */
    public static function listSent( int $limit = 50 ): array {
        $stmt = Database::pdo()->prepare(
            'SELECT course, course_year, course_month, sent_at
             FROM threshold_alerts_sent
             ORDER BY sent_at DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $out = array();
        foreach ($rows as $row) {
            $out[] = array(
                'course'       => (string) $row['course'],
                'course_year'  => (int) $row['course_year'],
                'course_month' => (int) $row['course_month'],
                'sent_at'      => (string) $row['sent_at'],
            );
        }
        return $out;
    }
}
