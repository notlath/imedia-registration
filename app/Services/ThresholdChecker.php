<?php

/**
 * IMedia Registration — ThresholdChecker service.
 *
 * Business rule: when a registration transitions to status='confirm',
 * count the confirm rows for the (course, year, month) slot. If the
 * count >= settings.alert_threshold, enqueue ONE threshold-alert email
 * via OutboxEmail. The threshold_alerts_sent UNIQUE KEY blocks duplicates.
 *
 * Per php-pro: strict types, final class.
 * Per wordpress-pro: uses {{token}} placeholders in the subject/body.
 * Per database-optimizer: one COUNT(*) per call (indexed).
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Models\{OutboxEmail, Registration, Setting, ThresholdAlert};

final class ThresholdChecker {
    /**
     * Run the threshold check for the given registration. No-op if the
     * registration is missing or its status is not 'confirm'.
     */
    public static function checkAndAlert( int $registrationId ): void {
        $reg = Registration::find($registrationId);
        if ($reg === null || ( $reg['status'] ?? null ) !== 'confirm') {
            return;
        }

        $threshold = (int) Setting::get('alert_threshold', 9);
        if ($threshold <= 0) {
            return;
        }

        $year  = (int) ( new \DateTimeImmutable((string) $reg['start_date']) )->format('Y');
        $month = (int) ( new \DateTimeImmutable((string) $reg['start_date']) )->format('n');
        $count = Registration::countConfirmedForSlot((string) $reg['course'], $year, $month);

        if ($count < $threshold) {
            return;
        }

        $fresh = ThresholdAlert::recordAndAlert((string) $reg['course'], $year, $month);
        if (! $fresh) {
            return; // Already alerted for this slot.
        }

        // Queue the alert email.
        $toEmail = (string) Setting::get('threshold_alert_to', '');
        if ($toEmail === '') {
            Logger::warning(
                'threshold.alert.skipped_no_recipient',
                array(
                    'course' => $reg['course'],
                    'year'   => $year,
                    'month'  => $month,
                )
            );
            return;
        }

        $subjectTpl = (string) Setting::get(
            'threshold_alert_subject',
            'Course Capacity Reached: {{course}} ({{monthName}} {{year}})'
        );
        $bodyTpl = (string) Setting::get(
            'threshold_alert_body',
            '<p>{{course}} has reached capacity for {{monthName}} {{year}}: {{count}} confirmed students (threshold: {{threshold}}).</p>'
        );

        $monthName = ( new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)) )->format('F');
        $tokens = array(
            '{{course}}'    => (string) $reg['course'],
            '{{year}}'      => (string) $year,
            '{{month}}'     => (string) $month,
            '{{monthName}}' => $monthName,
            '{{count}}'     => (string) $count,
            '{{threshold}}' => (string) $threshold,
        );
        $subject = strtr($subjectTpl, $tokens);
        $body    = strtr($bodyTpl, $tokens);

        $id = OutboxEmail::enqueue(
            $toEmail,
            $subject,
            $body,
            array(
                'kind'      => 'threshold_alert',
                'course'    => $reg['course'],
                'year'      => $year,
                'month'     => $month,
                'count'     => $count,
                'threshold' => $threshold,
            )
        );

        Logger::info(
            'threshold.alert.queued',
            array(
                'outbox_id' => $id,
                'course'    => $reg['course'],
                'year'      => $year,
                'month'     => $month,
                'count'     => $count,
            )
        );
    }
}
