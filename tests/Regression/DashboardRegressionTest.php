<?php
declare(strict_types=1);

namespace IMF\Tests\Regression;

use App\Core\{Csrf, Request, Response, Router, Session};
use IMF\Tests\Support\DatabaseTestCase;

class DashboardRegressionTest extends DatabaseTestCase
{
    public function test_authenticated_dashboard_returns_200(): void
    {
        Session::start();
        Session::put('_admin', ['id' => 1, 'name' => 'A', 'email' => 'a@a.com', 'role' => 'super']);

        $router = new Router();
        (require dirname(__DIR__, 2) . '/routes.php')($router);

        $res = $router->dispatch(new Request('GET', '/admin', [], [], [], []), new Response());
        $this->assertSame(200, $res->getStatus());
    }

    public function test_unauthenticated_dashboard_redirects(): void
    {
        $router = new Router();
        (require dirname(__DIR__, 2) . '/routes.php')($router);

        $res = $router->dispatch(new Request('GET', '/admin', [], [], [], []), new Response());
        $this->assertSame(302, $res->getStatus());
    }
}
