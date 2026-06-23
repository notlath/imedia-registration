<?php
declare(strict_types=1);

namespace IMF\Tests\Unit\Core;

use App\Core\{Router, Request, Response};
use IMF\Tests\Support\AppTestCase;

/**
 * Tier 1 — Unit tests for Router.
 *
 * @covers \App\Core\Router
 */
class RouterTest extends AppTestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new Router();
    }

    public function test_get_route_matches(): void
    {
        $this->router->get('/test', function (Request $req, Response $res) {
            return $res->text('matched');
        });

        $res = $this->router->dispatch(new Request('GET', '/test', [], [], [], []), new Response());
        $this->assertSame(200, $res->getStatus());
        $this->assertSame('matched', $res->getBody());
    }

    public function test_post_route_does_not_match_get(): void
    {
        $this->router->post('/test', function () { return new Response(); });
        $res = $this->router->dispatch(new Request('GET', '/test', [], [], [], []), new Response());
        $this->assertSame(405, $res->getStatus());
    }

    public function test_returns_404_for_unknown_route(): void
    {
        $res = $this->router->dispatch(new Request('GET', '/nonexistent', [], [], [], []), new Response());
        $this->assertSame(404, $res->getStatus());
    }

    public function test_captures_path_params(): void
    {
        $this->router->get('/users/{id}', function (Request $req, Response $res) {
            return $res->text('user-' . $req->param('id'));
        });

        $res = $this->router->dispatch(new Request('GET', '/users/42', [], [], [], []), new Response());
        $this->assertSame('user-42', $res->getBody());
    }

    public function test_middleware_executes_before_handler(): void
    {
        $this->router->get('/guarded', function (Request $req, Response $res) {
            return $res->text('ok');
        }, [
            new class() {
                public function __invoke(Request $req, Response $res, callable $next): Response {
                    return $next($req, $res);
                }
            },
        ]);

        $res = $this->router->dispatch(new Request('GET', '/guarded', [], [], [], []), new Response());
        $this->assertSame('ok', $res->getBody());
    }

    public function test_405_includes_allow_header(): void
    {
        $this->router->post('/test', function () { return new Response(); });
        $res = $this->router->dispatch(new Request('GET', '/test', [], [], [], []), new Response());
        $this->assertSame(405, $res->getStatus());
    }
}
