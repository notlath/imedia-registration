<?php

/**
 * IMedia Registration — ContactsController.
 *
 * Phase 4: filterable list + soft delete + CSV link. No per-row edit
 * (per the open-question answer). The CSV export endpoint already exists
 * at /admin/export/contacts.csv.
 *
 * Per php-pro: strict types, readonly controller.
 * Per wordpress-pro: CSRF + AdminAuth on every state-changing action.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Config, Csrf, Request, Response, Session, View};
use App\Models\Contact;

final readonly class ContactsController
{
    private const PER_PAGE = 25;

    public function index(Request $req, Response $res): Response
    {
        $filters = [
            'status' => (string) $req->query('status', ''),
            'search' => (string) $req->query('search', ''),
        ];
        $page = max(1, (int) $req->query('page', 1));

        $result = Contact::paginate($filters, $page, self::PER_PAGE);

        return $res->view('admin.contacts.list', [
            '__title'    => 'Contacts',
            'baseUrl'    => $this->baseUrl(),
            'filters'    => $filters,
            'page'       => $result['page'],
            'pages'      => $result['pages'],
            'total'      => $result['total'],
            'rows'       => $result['rows'],
            'perPage'    => $result['perPage'],
            'statuses'   => Contact::STATUSES,
            'csrf'       => Csrf::token(),
            'flash'      => Session::pullFlash('flash'),
            'flashError' => Session::pullFlash('flash_error'),
        ], 'admin');
    }

    public function delete(Request $req, Response $res): Response
    {
        $id = (int) $req->param('id');
        Contact::softDelete($id);
        Session::flash('flash', 'Contact #' . $id . ' moved to trash.');
        return $res->redirect($this->baseUrl() . '/admin/contacts');
    }

    private function baseUrl(): string
    {
        return rtrim((string) Config::get('BASE_URL', ''), '/');
    }
}
