<?php
declare(strict_types=1);

namespace IMF\Tests\Regression;

use App\Core\{Csrf, Request, Response, Router, Session};
use IMF\Tests\Support\DatabaseTestCase;

/**
 * Regression: CSV export.
 */
class ExportRegressionTest extends DatabaseTestCase
{
    public function test_exports_are_accessible(): void
    {
        Session::start();
        Session::put('_admin', ['id' => 1, 'name' => 'A', 'email' => 'a@a.com', 'role' => 'super']);

        foreach (['registrations', 'contacts', 'applications-ojt', 'applications-trainer'] as $type) {
            $router = new Router();
            (require dirname(__DIR__, 2) . '/routes.php')($router);

            $res = $router->dispatch(
                new Request('GET', "/admin/export/{$type}.csv", [], [], [], []),
                new Response()
            );
            $this->assertSame(200, $res->getStatus(), "Export {$type} should be 200");
        }
    }
}
