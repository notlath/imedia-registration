<?php
declare(strict_types=1);

namespace IMF\Tests\Regression;

use App\Core\{Csrf, Request, Response, Router, Session};
use IMF\Tests\Support\DatabaseTestCase;

class ApplicationsRegressionTest extends DatabaseTestCase
{
    public function test_ojt_list_accessible(): void
    {
        Session::start();
        Session::put('_admin', ['id' => 1, 'name' => 'A', 'email' => 'a@a.com', 'role' => 'super']);

        $router = new Router();
        (require dirname(__DIR__, 2) . '/routes.php')($router);

        $res = $router->dispatch(new Request('GET', '/admin/applications/ojt', [], [], [], []), new Response());
        $this->assertSame(200, $res->getStatus());
    }

    public function test_trainer_list_accessible(): void
    {
        Session::start();
        Session::put('_admin', ['id' => 1, 'name' => 'A', 'email' => 'a@a.com', 'role' => 'super']);

        $router = new Router();
        (require dirname(__DIR__, 2) . '/routes.php')($router);

        $res = $router->dispatch(new Request('GET', '/admin/applications/trainer', [], [], [], []), new Response());
        $this->assertSame(200, $res->getStatus());
    }

    public function test_applications_base_redirects(): void
    {
        Session::start();
        Session::put('_admin', ['id' => 1, 'name' => 'A', 'email' => 'a@a.com', 'role' => 'super']);

        $router = new Router();
        (require dirname(__DIR__, 2) . '/routes.php')($router);

        $res = $router->dispatch(new Request('GET', '/admin/applications', [], [], [], []), new Response());
        $this->assertSame(404, $res->getStatus(), '/admin/applications without type should 404');
    }
}
