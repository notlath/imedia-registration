<?php

/**
 * IMedia Registration — OutboxController.
 *
 * Phase 5: the admin-facing surface for the outbox. Three actions:
 *   - index:    render the queued/sent/failed tabs
 *   - process:  run the worker (admin button)
 *   - retry:    re-queue a single failed row
 *
 * Per php-pro: strict types, readonly controller.
 * Per wordpress-pro: CSRF on every state-changing form post.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Config, Csrf, Request, Response, Session};
use App\Models\OutboxEmail;
use App\Services\OutboxWorker;

final readonly class OutboxController
{
    private const TABS = ['queued', 'sent', 'failed'];

    public function index(Request $req, Response $res): Response
    {
        $tab = (string) $req->query('tab', 'queued');
        if (!in_array($tab, self::TABS, true)) {
            $tab = 'queued';
        }
        $limit = 50;
        $rows  = OutboxEmail::listByStatus($tab, $limit);

        $counts = OutboxEmail::countsByStatus(self::TABS);

        return $res->view('admin.outbox.index', [
            '__title'  => 'Outbox',
            'baseUrl'  => $this->baseUrl(),
            'tab'      => $tab,
            'tabs'     => self::TABS,
            'rows'     => $rows,
            'counts'   => $counts,
            'csrf'     => Csrf::token(),
            'flash'    => Session::pullFlash('flash'),
            'flashErr' => Session::pullFlash('flash_error'),
        ], 'admin');
    }

    public function process(Request $req, Response $res): Response
    {
        $result = OutboxWorker::run(25, 20);
        $msg = sprintf(
            'Processed: %d sent, %d failed, %d requeued, %d remaining (in %d ms).',
            $result['sent'],
            $result['failed'],
            $result['requeued'],
            $result['remaining'],
            $result['elapsed_ms']
        );
        if ($result['sent'] > 0 || $result['failed'] > 0 || $result['requeued'] > 0) {
            Session::flash('flash', $msg);
        } else {
            Session::flash('flash', 'Outbox is empty — nothing to process.');
        }
        return $res->redirect($this->baseUrl() . '/admin/outbox?tab=' . ($result['remaining'] > 0 ? 'queued' : 'sent'));
    }

    public function retry(Request $req, Response $res): Response
    {
        $id = (int) $req->param('id');
        if ($id <= 0) {
            Session::flash('flash_error', 'Invalid outbox id.');
            return $res->redirect($this->baseUrl() . '/admin/outbox?tab=failed');
        }
        $ok = OutboxEmail::retry($id);
        if ($ok) {
            Session::flash('flash', "Outbox #{$id} re-queued. Process the outbox to send it.");
        } else {
            Session::flash('flash_error', "Outbox #{$id} is not in 'failed' status (or does not exist).");
        }
        return $res->redirect($this->baseUrl() . '/admin/outbox?tab=' . ($ok ? 'queued' : 'failed'));
    }

    private function baseUrl(): string
    {
        return rtrim((string) Config::get('BASE_URL', ''), '/');
    }
}
