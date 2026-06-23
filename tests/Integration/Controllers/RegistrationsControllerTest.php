<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Controllers;

use App\Core\{Csrf, Request, Response, Session};
use App\Controllers\RegistrationsController;
use App\Models\Registration;
use IMF\Tests\Support\ControllerTestCase;

/**
 * Priority 1 — RegistrationsController integration tests.
 *
 * @covers \App\Controllers\RegistrationsController
 */
class RegistrationsControllerTest extends ControllerTestCase
{
    private RegistrationsController $controller;
    private string $csrfToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new RegistrationsController();
        Session::start();
        Session::put('_admin', ['id' => 1, 'name' => 'Admin', 'email' => 'admin@example.com', 'role' => 'super']);
        $this->csrfToken = Csrf::token();
    }

    private function insertTestRegistration(): int
    {
        return Registration::insert([
            'name'       => 'Test Person',
            'email'      => 'test-' . time() . '@test.com',
            'course'     => 'Test Course',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-08-01',
            'status'     => 'pending',
        ]);
    }

    // -----------------------------------------------------------------
    // New form
    // -----------------------------------------------------------------

    public function test_new_form_returns_200(): void
    {
        $req = new Request('GET', '/admin/registrations/new', [], [], [], []);
        $res = new Response();

        $result = $this->controller->newForm($req, $res);
        $this->assertSame(200, $result->getStatus());
        $this->assertStringContainsString('_csrf', $result->getBody());
    }

    // -----------------------------------------------------------------
    // Create
    // -----------------------------------------------------------------

    public function test_create_valid_registration(): void
    {
        $req = new Request('POST', '/admin/registrations', [], [
            '_csrf' => $this->csrfToken,
            'reg' => [
                'name'       => 'Alice',
                'email'      => 'alice-create@test.com',
                'course'     => 'PHP',
                'start_date' => '2026-07-01',
                'end_date'   => '2026-08-01',
                'status'     => 'pending',
                'payment_status' => 'pending',
            ],
        ], [], []);
        $res = new Response();

        $result = $this->controller->create($req, $res);
        $this->assertSame(302, $result->getStatus());

        // Find by email
        $all = Registration::allForExport();
        $found = null;
        foreach ($all as $r) {
            if ($r['email'] === 'alice-create@test.com') {
                $found = $r;
                break;
            }
        }
        $this->assertNotNull($found, 'Registration should exist');
        $this->assertSame('Alice', $found['name']);
    }

    public function test_create_missing_name_fails(): void
    {
        $req = new Request('POST', '/admin/registrations', [], [
            '_csrf' => $this->csrfToken,
            'reg' => [
                'email'      => 'miss@test.com',
                'course'     => 'PHP',
                'start_date' => '2026-07-01',
                'end_date'   => '2026-08-01',
            ],
        ], [], []);
        $res = new Response();

        $result = $this->controller->create($req, $res);
        $this->assertSame(302, $result->getStatus());
        Session::start();
        $errors = Session::pullFlash('errors');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('name', $errors);
    }

    // -----------------------------------------------------------------
    // View
    // -----------------------------------------------------------------

    public function test_view_existing(): void
    {
        $id = $this->insertTestRegistration();
        $req = new Request('GET', "/admin/registrations/{$id}", [], [], [], [], ['id' => (string) $id]);
        $res = new Response();

        $result = $this->controller->view($req, $res);
        $this->assertSame(200, $result->getStatus());
        $this->assertStringContainsString('Test Person', $result->getBody());
    }

    public function test_view_nonexistent_returns_404(): void
    {
        $req = new Request('GET', '/admin/registrations/999999', [], [], [], [], ['id' => '999999']);
        $res = new Response();

        $result = $this->controller->view($req, $res);
        $this->assertSame(404, $result->getStatus());
    }

    // -----------------------------------------------------------------
    // Update
    // -----------------------------------------------------------------

    public function test_update_valid(): void
    {
        $id = $this->insertTestRegistration();
        $req = new Request('POST', "/admin/registrations/{$id}", [], [
            '_csrf' => $this->csrfToken,
            'reg' => [
                'name'   => 'Updated Name',
                'email'  => 'updated99@test.com',
                'course' => 'Math',
                'start_date' => '2026-07-01',
                'end_date'   => '2026-08-01',
                'status' => 'pending',
                'payment_status' => 'pending',
            ],
        ], [], [], ['id' => (string) $id]);
        $res = new Response();

        $result = $this->controller->update($req, $res);
        $this->assertSame(302, $result->getStatus());

        Session::start();
        $errors = Session::pullFlash('errors');
        $this->assertNull($errors, 'No validation errors should be flashed');

        $row = Registration::find($id);
        $this->assertIsArray($row);
        $this->assertSame('Updated Name', $row['name']);
    }

    // -----------------------------------------------------------------
    // Soft delete + restore
    // -----------------------------------------------------------------

    public function test_delete_marks_deleted(): void
    {
        $id = $this->insertTestRegistration();
        $req = new Request('POST', "/admin/registrations/{$id}/delete", [], [
            '_csrf' => $this->csrfToken,
        ], [], [], ['id' => (string) $id]);
        $res = new Response();

        $result = $this->controller->delete($req, $res);
        $this->assertSame(302, $result->getStatus());

        $row = Registration::find($id);
        $this->assertNotNull($row['deleted_at']);
    }

    public function test_restore_clears_deleted(): void
    {
        $id = $this->insertTestRegistration();
        Registration::softDelete($id);

        $req = new Request('POST', "/admin/registrations/{$id}/restore", [], [
            '_csrf' => $this->csrfToken,
        ], [], [], ['id' => (string) $id]);
        $res = new Response();

        $result = $this->controller->restore($req, $res);
        $this->assertSame(302, $result->getStatus());

        $row = Registration::find($id);
        $this->assertNull($row['deleted_at']);
    }
}
