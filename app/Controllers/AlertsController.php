<?php

/**
 * IMedia Registration — AlertsController.
 *
 * Phase 3: list course slots that are at or over the alert threshold.
 * The dashboard banner reads from the same query.
 *
 * Per php-pro: strict types, readonly controller.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Config, Request, Response, Session};
use App\Models\{Registration, Setting, ThresholdAlert};

final readonly class AlertsController {
    public function index( Request $req, Response $res ): Response {
        $threshold = (int) Setting::get('alert_threshold', 9);
        $slots     = $threshold > 0 ? Registration::thresholdSlots($threshold, 50) : array();
        $sent      = ThresholdAlert::listSent(50);

        return $res->view(
            'admin.alerts',
            array(
                '__title'   => 'Threshold alerts',
                'baseUrl'   => $this->baseUrl(),
                'threshold' => $threshold,
                'slots'     => $slots,
                'sent'      => $sent,
                'flash'     => Session::pullFlash('flash'),
            ),
            'admin'
        );
    }

    private function baseUrl(): string {
        return rtrim((string) Config::get('BASE_URL', ''), '/');
    }
}
