<?php
declare(strict_types=1);

namespace IMF\Tests\Regression;

use App\Core\{Csrf, Request, Response, Router, Session};
use App\Models\Registration;
use IMF\Tests\Support\DatabaseTestCase;

/**
 * Regression: Registration lifecycle.
 */
class RegistrationRegressionTest extends DatabaseTestCase
{
    private Router $router;
    private string $csrfToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new Router();
        (require dirname(__DIR__, 2) . '/routes.php')($this->router);

        Session::start();
        Session::put('_admin', ['id' => 1, 'name' => 'A', 'email' => 'a@a.com', 'role' => 'super']);
        $this->csrfToken = Csrf::token();
    }

    public function test_list_page(): void
    {
        $res = $this->router->dispatch(new Request('GET', '/admin/registrations', [], [], [], []), new Response());
        $this->assertSame(200, $res->getStatus());
    }

    public function test_create_flow(): void
    {
        $res = $this->router->dispatch(new Request('POST', '/admin/registrations', [], [
            '_csrf' => $this->csrfToken,
            'reg' => [
                'name'       => 'Regression',
                'email'      => 'reg@test.com',
                'course'     => 'Test',
                'start_date' => '2026-07-01',
                'end_date'   => '2026-08-01',
                'status'     => 'pending',
                'payment_status' => 'pending',
            ],
        ], [], []), new Response());
        $this->assertSame(302, $res->getStatus());
    }

    public function test_new_form_page(): void
    {
        $res = $this->router->dispatch(new Request('GET', '/admin/registrations/new', [], [], [], []), new Response());
        $this->assertSame(200, $res->getStatus());
    }
}
