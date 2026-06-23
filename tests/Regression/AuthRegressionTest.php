<?php
declare(strict_types=1);

namespace IMF\Tests\Regression;

use App\Core\{Csrf, Request, Response, Router, Session};
use IMF\Tests\Support\DatabaseTestCase;

/**
 * Regression: Authentication flow.
 *
 * Run: ./vendor/bin/phpunit --testsuite regression --filter AuthRegressionTest
 */
class AuthRegressionTest extends DatabaseTestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new Router();
        (require dirname(__DIR__, 2) . '/routes.php')($this->router);
    }

    public function test_protected_route_redirects_when_unauthenticated(): void
    {
        $res = $this->router->dispatch(new Request('GET', '/admin/registrations', [], [], [], []), new Response());
        $this->assertSame(302, $res->getStatus());
    }

    public function test_public_route_accessible(): void
    {
        $res = $this->router->dispatch(new Request('GET', '/admin/login', [], [], [], []), new Response());
        $this->assertSame(200, $res->getStatus());
    }

    public function test_login_creates_session(): void
    {
        Session::start();
        $token = Csrf::token();
        $res = $this->router->dispatch(new Request('POST', '/admin/login', [], [
            'email'    => 'admin@example.com',
            'password' => 'admin123',
            '_csrf'    => $token,
        ], [], []), new Response());

        $this->assertSame(302, $res->getStatus());
        $this->assertNotNull(Session::get('_admin'));
    }

    public function test_api_submit_rejects_without_hmac(): void
    {
        $res = $this->router->dispatch(new Request('POST', '/api/submit', [], [
            'form_id' => 9999,
            'fields'  => [],
        ], [], []), new Response());
        $this->assertSame(401, $res->getStatus());
    }
}
