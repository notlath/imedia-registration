<?php

/**
 * IMedia Registration — ApplicationsController.
 *
 * Phase 4: filterable list per type (ojt|trainer) + soft delete + CSV
 * link. No per-row edit. CSV export endpoints already exist at
 * /admin/export/applications-{ojt,trainer}.csv.
 *
 * Per php-pro: strict types, readonly controller.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Config, Csrf, Request, Response, Session, View};
use App\Models\Application;

final readonly class ApplicationsController {
    private const PER_PAGE = 25;

    public function index( Request $req, Response $res ): Response {
        $type = (string) $req->param('type');
        if (! in_array($type, Application::TYPES, true)) {
            return $res->error(404, 'Unknown application type: ' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8'));
        }
        $filters = array(
            'status' => (string) $req->query('status', ''),
            'search' => (string) $req->query('search', ''),
        );
        $page = max(1, (int) $req->query('page', 1));

        $result = Application::paginate($type, $filters, $page, self::PER_PAGE);

        return $res->view(
            'admin.applications.list',
            array(
                '__title'    => $type === Application::TYPE_OJT ? 'OJT Applications' : 'Trainer Applications',
                'baseUrl'    => $this->baseUrl(),
                'type'       => $type,
                'filters'    => $filters,
                'page'       => $result['page'],
                'pages'      => $result['pages'],
                'total'      => $result['total'],
                'rows'       => $result['rows'],
                'perPage'    => $result['perPage'],
                'statuses'   => Application::STATUSES,
                'csrf'       => Csrf::token(),
                'flash'      => Session::pullFlash('flash'),
                'flashError' => Session::pullFlash('flash_error'),
            ),
            'admin'
        );
    }

    public function delete( Request $req, Response $res ): Response {
        $type = (string) $req->param('type');
        if (! in_array($type, Application::TYPES, true)) {
            return $res->error(404, 'Unknown application type.');
        }
        $id = (int) $req->param('id');
        Application::softDelete($id);
        Session::flash('flash', ucfirst($type) . ' application #' . $id . ' moved to trash.');
        return $res->redirect($this->baseUrl() . '/admin/applications/' . $type);
    }

    private function baseUrl(): string {
        return rtrim((string) Config::get('BASE_URL', ''), '/');
    }
}
