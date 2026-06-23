<?php
declare(strict_types=1);

namespace IMF\Tests\Unit\Middleware;

use App\Core\{Csrf, Request, Response, Session};
use App\Middleware\CsrfVerify;
use IMF\Tests\Support\AppTestCase;

/**
 * Tier 1 — Unit tests for CsrfVerify middleware.
 *
 * @covers \App\Middleware\CsrfVerify
 */
class CsrfVerifyTest extends AppTestCase
{
    private CsrfVerify $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        Session::start();
        Session::forget('_csrf_token');
        $this->middleware = new CsrfVerify();
    }

    public function test_passes_when_csrf_matches(): void
    {
        $token = Csrf::token();
        $req = new Request('POST', '/admin/registrations', [], ['_csrf' => $token], [], []);
        $res = new Response();
        $nextCalled = false;
        $next = function () use (&$nextCalled) { $nextCalled = true; return new Response(); };

        $result = ($this->middleware)($req, $res, $next);
        $this->assertTrue($nextCalled);
    }

    public function test_returns_419_on_mismatch(): void
    {
        Session::start();
        Csrf::token();
        $req = new Request('POST', '/admin/registrations', [], ['_csrf' => 'bad-token'], [], []);
        $res = new Response();
        $next = function () { return new Response(); };

        $result = ($this->middleware)($req, $res, $next);
        $this->assertSame(419, $result->getStatus());
    }

    public function test_returns_419_when_csrf_missing(): void
    {
        $req = new Request('POST', '/admin/registrations', [], [], [], []);
        $res = new Response();
        $next = function () { return new Response(); };

        $result = ($this->middleware)($req, $res, $next);
        $this->assertSame(419, $result->getStatus());
    }

    public function test_removes_token_on_failure(): void
    {
        Session::start();
        Csrf::token();
        $req = new Request('POST', '/admin/registrations', [], ['_csrf' => 'bad'], [], []);
        $res = new Response();
        $next = function () { return new Response(); };

        ($this->middleware)($req, $res, $next);
        $this->assertNull(Session::get('_csrf_token'));
    }
}
