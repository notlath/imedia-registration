<?php
declare(strict_types=1);

namespace IMF\Tests\Unit\Middleware;

use App\Core\{Auth, Request, Response, Session};
use App\Middleware\AdminAuth;
use IMF\Tests\Support\AppTestCase;

/**
 * Tier 1 — Unit tests for AdminAuth middleware.
 *
 * @covers \App\Middleware\AdminAuth
 */
class AdminAuthTest extends AppTestCase
{
    private AdminAuth $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AdminAuth();
    }

    public function test_redirects_unauthenticated_to_login(): void
    {
        Session::start();
        Session::forget('_admin');

        $req = new Request('GET', '/admin/registrations', [], [], [], []);
        $res = new Response();
        $nextCalled = false;
        $next = function () use (&$nextCalled) { $nextCalled = true; return new Response(); };

        $result = ($this->middleware)($req, $res, $next);

        $this->assertFalse($nextCalled);
        $this->assertSame(302, $result->getStatus());
    }

    public function test_allows_authenticated_request(): void
    {
        Session::start();
        Session::put('_admin', [
            'id'    => 1,
            'name'  => 'Admin',
            'email' => 'admin@example.com',
            'role'  => 'super',
        ]);

        $req = new Request('GET', '/admin/registrations', [], [], [], []);
        $res = new Response();
        $nextCalled = false;
        $next = function (Request $r, Response $rr) use (&$nextCalled) {
            $nextCalled = true;
            return $rr;
        };

        $result = ($this->middleware)($req, $res, $next);

        $this->assertTrue($nextCalled);
        $this->assertSame(200, $result->getStatus());
    }
}
