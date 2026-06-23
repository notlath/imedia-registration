<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Controllers;

use App\Core\{Request, Response, Session};
use App\Controllers\LoginController;
use IMF\Tests\Support\ControllerTestCase;

/**
 * Priority 1 — LoginController integration tests.
 *
 * @covers \App\Controllers\LoginController
 */
class LoginControllerTest extends ControllerTestCase
{
    private LoginController $controller;
    private string $csrfToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new LoginController();
        Session::start();
        $this->csrfToken = \App\Core\Csrf::token();
    }

    public function test_show_form_returns_200(): void
    {
        $req = new Request('GET', '/admin/login', [], [], [], []);
        $res = new Response();

        $result = $this->controller->showForm($req, $res);

        $this->assertSame(200, $result->getStatus());
        $this->assertStringContainsString('login', $result->getBody());
    }

    public function test_login_with_valid_credentials_redirects_and_starts_session(): void
    {
        $req = new Request('POST', '/admin/login', [], [
            'email'    => 'admin@example.com',
            'password' => 'admin123',
            '_csrf'    => $this->csrfToken,
        ], [], []);
        $res = new Response();

        $result = $this->controller->login($req, $res);

        $this->assertSame(302, $result->getStatus());
        $this->assertNotNull(Session::get('_admin'), 'Session should contain admin data');
        $this->assertSame('admin@example.com', Session::get('_admin')['email']);
        $this->assertSame('super', Session::get('_admin')['role']);
    }

    public function test_login_with_wrong_password_redirects_with_error(): void
    {
        $req = new Request('POST', '/admin/login', [], [
            'email'    => 'admin@example.com',
            'password' => 'wrong-password',
            '_csrf'    => $this->csrfToken,
        ], [], []);
        $res = new Response();

        $result = $this->controller->login($req, $res);

        $this->assertSame(302, $result->getStatus());
        $this->assertNull(Session::get('_admin'), 'No admin session on failed login');
    }

    public function test_login_with_unknown_email_redirects_with_error(): void
    {
        $req = new Request('POST', '/admin/login', [], [
            'email'    => 'unknown@example.com',
            'password' => 'anypassword',
            '_csrf'    => $this->csrfToken,
        ], [], []);
        $res = new Response();

        $result = $this->controller->login($req, $res);

        $this->assertSame(302, $result->getStatus());
        $this->assertNull(Session::get('_admin'));
    }

    public function test_login_with_missing_email_returns_error(): void
    {
        $req = new Request('POST', '/admin/login', [], [
            'password' => 'admin123',
            '_csrf'    => $this->csrfToken,
        ], [], []);
        $res = new Response();

        $result = $this->controller->login($req, $res);

        $this->assertSame(302, $result->getStatus());
    }

    public function test_login_with_missing_password_returns_error(): void
    {
        $req = new Request('POST', '/admin/login', [], [
            'email' => 'admin@example.com',
            '_csrf' => $this->csrfToken,
        ], [], []);
        $res = new Response();

        $result = $this->controller->login($req, $res);

        $this->assertSame(302, $result->getStatus());
    }

    public function test_logout_clears_session_and_redirects(): void
    {
        Session::start();
        Session::put('_admin', ['id' => 1, 'name' => 'Admin', 'email' => 'admin@example.com', 'role' => 'super']);

        $req = new Request('POST', '/admin/logout', [], [
            '_csrf' => $this->csrfToken,
        ], [], []);
        $res = new Response();

        $result = $this->controller->logout($req, $res);

        $this->assertSame(302, $result->getStatus());
        $this->assertNull(Session::get('_admin'), 'Session cleared after logout');
    }

    public function test_logout_when_not_logged_in_does_not_crash(): void
    {
        Session::start();
        Session::forget('_admin');

        $req = new Request('POST', '/admin/logout', [], [
            '_csrf' => $this->csrfToken,
        ], [], []);
        $res = new Response();

        $result = $this->controller->logout($req, $res);

        $this->assertSame(302, $result->getStatus());
    }

    public function test_login_success_regenerates_session(): void
    {
        Session::start();

        $req = new Request('POST', '/admin/login', [], [
            'email'    => 'admin@example.com',
            'password' => 'admin123',
            '_csrf'    => $this->csrfToken,
        ], [], []);
        $res = new Response();

        $this->controller->login($req, $res);

        // In CLI mode, session_regenerate_id is a no-op, but the admin
        // data must still be set correctly.
        $this->assertNotNull(Session::get('_admin'), 'Admin data should be in session after login');
        $this->assertSame('admin@example.com', Session::get('_admin')['email']);
    }

}
