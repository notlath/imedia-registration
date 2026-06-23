<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Controllers;

use App\Core\{Csrf, Request, Response, Session};
use App\Controllers\ProfileController;
use IMF\Tests\Support\ControllerTestCase;
use IMF\Tests\Support\Fixtures;

class ProfileControllerTest extends ControllerTestCase
{
    use Fixtures;

    private ProfileController $controller;
    private string $csrfToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ProfileController();
        $this->createAdminSession();
        $this->csrfToken = $this->createCsrfToken();
    }

    public function test_show_form_returns_200(): void
    {
        $req = new Request('GET', '/admin/profile', [], [], [], []);
        $res = new Response();
        $result = $this->controller->show($req, $res);
        $this->assertSame(200, $result->getStatus());
    }

    public function test_update_valid(): void
    {
        $req = new Request('POST', '/admin/profile', [], [
            '_csrf' => $this->csrfToken,
            'first_name' => 'Super',
            'last_name'  => 'Admin',
            'email'      => 'admin@example.com',
        ], [], []);
        $res = new Response();
        $result = $this->controller->update($req, $res);
        $this->assertSame(302, $result->getStatus());
    }

    public function test_update_invalid_email_fails(): void
    {
        $req = new Request('POST', '/admin/profile', [], [
            '_csrf' => $this->csrfToken,
            'first_name' => 'Super',
            'last_name'  => 'Admin',
            'email'      => 'not-an-email',
        ], [], []);
        $res = new Response();
        $result = $this->controller->update($req, $res);
        $this->assertSame(302, $result->getStatus());

        Session::start();
        $errors = Session::pullFlash('errors');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('email', $errors ?? []);
    }
}
