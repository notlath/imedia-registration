<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Controllers;

use App\Core\{Csrf, Request, Response, Session};
use App\Controllers\UsersController;
use App\Models\Admin;
use IMF\Tests\Support\ControllerTestCase;
use IMF\Tests\Support\Fixtures;

class UsersControllerTest extends ControllerTestCase
{
    use Fixtures;

    private UsersController $controller;
    private string $csrfToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new UsersController();
        $this->createAdminSession();
        $this->csrfToken = $this->createCsrfToken();
    }

    private function createSecondAdmin(): int
    {
        return Admin::create('Second Admin', 'second@test.com', 'password123', 'admin');
    }

    public function test_new_form_returns_200(): void
    {
        $req = new Request('GET', '/admin/users/new', [], [], [], []);
        $res = new Response();
        $result = $this->controller->newForm($req, $res);
        $this->assertSame(200, $result->getStatus());
    }

    public function test_create_valid(): void
    {
        $req = new Request('POST', '/admin/users', [], [
            '_csrf' => $this->csrfToken,
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'password' => 'securePass123',
            'role' => 'admin',
        ], [], []);
        $res = new Response();
        $result = $this->controller->create($req, $res);
        $this->assertSame(302, $result->getStatus());

        $admin = Admin::findByEmail('newuser@test.com');
        $this->assertIsArray($admin);
        $this->assertSame('New User', $admin['name']);
    }

    public function test_create_duplicate_email_fails(): void
    {
        $this->createSecondAdmin();

        $req = new Request('POST', '/admin/users', [], [
            '_csrf' => $this->csrfToken,
            'name' => 'Duplicate',
            'email' => 'second@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ], [], []);
        $res = new Response();
        $result = $this->controller->create($req, $res);
        $this->assertSame(302, $result->getStatus());

        Session::start();
        $errorFlash = Session::pullFlash('error');
        $this->assertNotNull($errorFlash, 'An error should be flashed for duplicate email');
    }

    public function test_password_is_hashed(): void
    {
        $req = new Request('POST', '/admin/users', [], [
            '_csrf' => $this->csrfToken,
            'name' => 'Hash Test',
            'email' => 'hash@test.com',
            'password' => 'myPlainPass',
            'role' => 'admin',
        ], [], []);
        $res = new Response();
        $this->controller->create($req, $res);

        $user = Admin::findByEmail('hash@test.com');
        $this->assertNotSame('myPlainPass', $user['password']);
        $this->assertTrue(password_verify('myPlainPass', $user['password']));
    }

    public function test_edit_returns_200(): void
    {
        $id = $this->createSecondAdmin();
        $req = new Request('GET', "/admin/users/{$id}/edit", [], [], [], [], ['id' => (string) $id]);
        $res = new Response();
        $result = $this->controller->edit($req, $res);
        $this->assertSame(200, $result->getStatus());
    }

    public function test_update_valid(): void
    {
        $id = $this->createSecondAdmin();
        $req = new Request('POST', "/admin/users/{$id}", [], [
            '_csrf' => $this->csrfToken,
            'name' => 'Updated Second',
            'email' => 'second@test.com',
            'role' => 'admin',
        ], [], [], ['id' => (string) $id]);
        $res = new Response();
        $result = $this->controller->update($req, $res);
        $this->assertSame(302, $result->getStatus());

        $user = Admin::find($id);
        $this->assertSame('Updated Second', $user['name']);
    }

    public function test_delete_second_admin(): void
    {
        $id = $this->createSecondAdmin();
        $req = new Request('POST', "/admin/users/{$id}/delete", [], [
            '_csrf' => $this->csrfToken,
        ], [], [], ['id' => (string) $id]);
        $res = new Response();
        $result = $this->controller->delete($req, $res);
        $this->assertSame(302, $result->getStatus());

        $this->assertNull(Admin::find($id));
    }
}
