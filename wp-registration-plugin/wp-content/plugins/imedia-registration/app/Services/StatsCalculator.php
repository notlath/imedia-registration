<?php

/**
 * IMedia Registration — StatsCalculator service.
 *
 * Thin orchestration layer over Registration::stats() etc., packaged
 * so controllers can call one method and get a single shape.
 *
 * Per php-pro: strict types, final class, static methods.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Registration;

final class StatsCalculator
{
    /**
     * Compute the full dashboard payload in one call.
     *
     * @return array{
     *   kpis: array<string, mixed>,
     *   series30: array{labels: array<int, string>, data: array<int, int>},
     *   statusBreakdown: array{labels: array<int, string>, data: array<int, int>},
     *   topCourses: array{labels: array<int, string>, data: array<int, int>},
     *   thresholdSlots: array<int, array<string, mixed>>
     * }
     */
    public static function dashboard(): array
    {
        $stats = Registration::stats();

        $byStatus = $stats['by_status'];
        $statusLabels = array_keys($byStatus);
        $statusData   = array_values($byStatus);

        $topCourses = Registration::topCoursesByConfirm(5);

        // Threshold slots — needs the threshold from settings.
        $threshold = (int) \App\Models\Setting::get('alert_threshold', 9);
        $slots = $threshold > 0 ? Registration::thresholdSlots($threshold, 20) : [];

        return [
            'kpis'            => $stats,
            'series30'        => Registration::registrations30DaySeries(),
            'statusBreakdown' => [
                'labels' => $statusLabels,
                'data'   => $statusData,
            ],
            'topCourses'      => $topCourses,
            'thresholdSlots'  => $slots,
        ];
    }
}
