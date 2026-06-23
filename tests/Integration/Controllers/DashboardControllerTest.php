<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Controllers;

use App\Core\{Request, Response, Session};
use App\Controllers\DashboardController;
use IMF\Tests\Support\ControllerTestCase;
use IMF\Tests\Support\Fixtures;

class DashboardControllerTest extends ControllerTestCase
{
    use Fixtures;

    private DashboardController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new DashboardController();
        $this->createAdminSession();
    }

    public function test_index_returns_200(): void
    {
        $req = new Request('GET', '/admin', [], [], [], []);
        $res = new Response();
        $result = $this->controller->index($req, $res);
        $this->assertSame(200, $result->getStatus());
        $this->assertStringContainsString('dashboard', strtolower($result->getBody()));
    }
}
