<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Controllers;

use App\Core\{Request, Response, Router};
use IMF\Tests\Support\ControllerTestCase;

/**
 * Priority 0 — Router edge cases.
 *
 * Every controller and regression test depends on Router correctness,
 * so these are validated first.
 *
 * @covers \App\Core\Router
 */
class RouterEdgeCasesTest extends ControllerTestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new Router();
    }

    public function test_dispatch_with_trailing_slash_matches_route(): void
    {
        $this->router->get('/test', function (Request $req, Response $res) {
            return $res->text('matched');
        });

        $res = $this->router->dispatch(new Request('GET', '/test/', [], [], [], []), new Response());
        $this->assertSame(200, $res->getStatus());
        $this->assertSame('matched', $res->getBody());
    }

    public function test_dispatch_with_multiple_trailing_slashes(): void
    {
        $this->router->get('/test', function (Request $req, Response $res) {
            return $res->text('matched');
        });

        $res = $this->router->dispatch(new Request('GET', '/test///', [], [], [], []), new Response());
        $this->assertSame(200, $res->getStatus());
    }

    public function test_404_for_unregistered_path(): void
    {
        $res = $this->router->dispatch(new Request('GET', '/never-registered', [], [], [], []), new Response());
        $this->assertSame(404, $res->getStatus());
        $this->assertStringContainsString('Not Found', $res->getBody());
    }

    public function test_405_for_wrong_method(): void
    {
        $this->router->post('/only-post', function () { return new Response(); });

        $res = $this->router->dispatch(new Request('GET', '/only-post', [], [], [], []), new Response());
        $this->assertSame(405, $res->getStatus());
    }

    public function test_405_allow_header_list_methods(): void
    {
        $this->router->post('/test', function () { return new Response(); });

        $res = $this->router->dispatch(new Request('GET', '/test', [], [], [], []), new Response());
        $this->assertSame(405, $res->getStatus());
    }

    public function test_path_param_extraction(): void
    {
        $this->router->get('/users/{id}', function (Request $req, Response $res) {
            return $res->text('user-' . $req->param('id'));
        });

        $res = $this->router->dispatch(new Request('GET', '/users/42', [], [], [], []), new Response());
        $this->assertSame('user-42', $res->getBody());
    }

    public function test_path_param_does_not_cross_segments(): void
    {
        $this->router->get('/users/{id}/edit', function (Request $req, Response $res) {
            return $res->text('edit-' . $req->param('id'));
        });

        $res = $this->router->dispatch(new Request('GET', '/users/5/edit', [], [], [], []), new Response());
        $this->assertSame('edit-5', $res->getBody());
        // /users/5 should not match /users/{id}/edit
        $res2 = $this->router->dispatch(new Request('GET', '/users/5', [], [], [], []), new Response());
        $this->assertSame(404, $res2->getStatus());
    }

    public function test_middleware_short_circuit_prevents_handler(): void
    {
        $this->router->get('/guarded', function (Request $req, Response $res) {
            return $res->text('handler');
        }, [
            new class() {
                public function __invoke(Request $req, Response $res, callable $next): Response {
                    return $res->error(403, 'Blocked');
                }
            },
        ]);

        $res = $this->router->dispatch(new Request('GET', '/guarded', [], [], [], []), new Response());
        $this->assertSame(403, $res->getStatus());
        $this->assertStringNotContainsString('handler', $res->getBody());
    }

    public function test_middleware_chain_executes_in_order(): void
    {
        unset($GLOBALS['_mw_log']);
        $this->router->get('/ordered', function (Request $req, Response $res) {
            return $res->text('done');
        }, [
            new class() {
                public function __invoke(Request $req, Response $res, callable $next): Response {
                    if (!isset($GLOBALS['_mw_log'])) $GLOBALS['_mw_log'] = [];
                    $GLOBALS['_mw_log'][] = 'first';
                    return $next($req, $res);
                }
            },
            new class() {
                public function __invoke(Request $req, Response $res, callable $next): Response {
                    if (!isset($GLOBALS['_mw_log'])) $GLOBALS['_mw_log'] = [];
                    $GLOBALS['_mw_log'][] = 'second';
                    return $next($req, $res);
                }
            },
        ]);

        $this->router->dispatch(new Request('GET', '/ordered', [], [], [], []), new Response());
        $this->assertSame(['first', 'second'], $GLOBALS['_mw_log']);
        unset($GLOBALS['_mw_log']);
    }

    public function test_clsoure_handler_returns_response(): void
    {
        $this->router->get('/closure', function (Request $req, Response $res) {
            return $res->json(['status' => 'ok']);
        });

        $res = $this->router->dispatch(new Request('GET', '/closure', [], [], [], []), new Response());
        $this->assertSame(200, $res->getStatus());
        $this->assertStringContainsString('"status":"ok"', $res->getBody());
    }

    public function test_get_does_not_match_post(): void
    {
        $this->router->get('/test', function () { return new Response(); });
        $res = $this->router->dispatch(new Request('POST', '/test', [], [], [], []), new Response());
        $this->assertSame(405, $res->getStatus());
    }
}
