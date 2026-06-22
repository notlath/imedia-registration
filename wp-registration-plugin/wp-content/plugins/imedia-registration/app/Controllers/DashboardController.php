<?php

/**
 * IMedia Registration — DashboardController.
 *
 * Phase 3: real KPI cards + 3 charts (line, donut, bar) + threshold
 * banner + per-course breakdown.
 *
 * Phase 6: chart configs are now data-only (type + data + minimal
 * structural options). Colors are injected by app.js at render time
 * from the CSS custom properties so dark mode flips correctly.
 *
 * Per php-pro: strict types, readonly controller.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Config, Request, Response, Session};
use App\Services\StatsCalculator;

final readonly class DashboardController
{
    public function index(Request $req, Response $res): Response
    {
        $admin = Auth::admin();
        $data  = StatsCalculator::dashboard();

        // Data-only chart configs. app.js reads the color palette from
        // CSS custom properties and merges it in at render time.
        $series30    = $data['series30'];
        $statusBreak = $data['statusBreakdown'];
        $topCourses  = $data['topCourses'];

        $series30Config = [
            'type'    => 'line',
            'data'    => [
                'labels'   => $series30['labels'],
                'datasets' => [[
                    'label' => 'New registrations',
                    'data'  => $series30['data'],
                    'fill'  => true,
                    'tension' => 0.25,
                    'pointRadius' => 2,
                ]],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins'             => ['legend' => ['display' => false]],
                'scales'               => [
                    'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                ],
            ],
        ];

        $statusConfig = [
            'type'    => 'doughnut',
            'data'    => [
                'labels'   => array_map(static fn ($s) => ucfirst((string) $s), $statusBreak['labels']),
                'datasets' => [[
                    'data'        => $statusBreak['data'],
                    'borderWidth' => 2,
                ]],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins'             => ['legend' => ['position' => 'bottom']],
            ],
        ];

        $topCoursesConfig = [
            'type'    => 'bar',
            'data'    => [
                'labels'   => $topCourses['labels'],
                'datasets' => [[
                    'label' => 'Confirmed students',
                    'data'  => $topCourses['data'],
                ]],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'indexAxis'           => 'y',
                'plugins'             => ['legend' => ['display' => false]],
                'scales'               => [
                    'x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                ],
            ],
        ];

        $series30ConfigJson  = json_encode($series30Config,  JSON_UNESCAPED_SLASHES);
        $statusConfigJson    = json_encode($statusConfig,    JSON_UNESCAPED_SLASHES);
        $topCoursesConfigJson = json_encode($topCoursesConfig, JSON_UNESCAPED_SLASHES);

        return $res->view('admin.dashboard', [
            '__title'              => 'Dashboard',
            'baseUrl'              => $this->baseUrl(),
            'admin'                => $admin,
            'kpis'                 => $data['kpis'],
            'thresholdSlots'       => $data['thresholdSlots'],
            'series30ConfigJson'   => $series30ConfigJson,
            'statusConfigJson'     => $statusConfigJson,
            'topCoursesConfigJson' => $topCoursesConfigJson,
            'series30Labels'       => $series30['labels'],
            'series30Data'         => $series30['data'],
            'statusLabels'         => array_map(static fn ($s) => ucfirst((string) $s), $statusBreak['labels']),
            'statusData'           => $statusBreak['data'],
            'topLabels'            => $topCourses['labels'],
            'topData'              => $topCourses['data'],
            'flash'                => Session::pullFlash('flash'),
            'csrf'                 => \App\Core\Csrf::token(),
        ], 'admin');
    }

    private function baseUrl(): string
    {
        return rtrim((string) Config::get('BASE_URL', ''), '/');
    }
}
