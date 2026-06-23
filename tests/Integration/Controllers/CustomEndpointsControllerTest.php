<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Controllers;

use App\Core\{Csrf, Request, Response, Session};
use App\Controllers\CustomEndpointsController;
use App\Models\CustomEndpoint;
use IMF\Tests\Support\ControllerTestCase;
use IMF\Tests\Support\Fixtures;

class CustomEndpointsControllerTest extends ControllerTestCase
{
    use Fixtures;

    private CustomEndpointsController $controller;
    private string $csrfToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new CustomEndpointsController();
        $this->createAdminSession();
        $this->csrfToken = $this->createCsrfToken();
    }

    public function test_new_form_returns_200(): void
    {
        $req = new Request('GET', '/admin/custom-endpoints/new', [], [], [], []);
        $res = new Response();
        $result = $this->controller->newForm($req, $res);
        $this->assertSame(200, $result->getStatus());
        $this->assertStringContainsString('_csrf', $result->getBody());
    }

    public function test_create_valid(): void
    {
        $slug = 'test-ce-' . time();
        $req = new Request('POST', '/admin/custom-endpoints', [], [
            '_csrf' => $this->csrfToken,
            'slug' => $slug,
            'name' => 'Test Endpoint',
        ], [], []);
        $res = new Response();
        $result = $this->controller->create($req, $res);
        $this->assertSame(302, $result->getStatus());

        $found = CustomEndpoint::findBySlug($slug);
        $this->assertIsArray($found);
        $this->assertSame('Test Endpoint', $found['name']);
    }

    public function test_create_empty_fields_and_statuses_json(): void
    {
        $slug = 'empty-fields-' . time();
        $req = new Request('POST', '/admin/custom-endpoints', [], [
            '_csrf' => $this->csrfToken,
            'slug' => $slug,
            'name' => 'Empty Fields',
        ], [], []);
        $res = new Response();
        $result = $this->controller->create($req, $res);
        $this->assertSame(302, $result->getStatus(), 'Empty fields/statuses JSON should not block creation');
    }

    public function test_create_invalid_slug_format(): void
    {
        $req = new Request('POST', '/admin/custom-endpoints', [], [
            '_csrf' => $this->csrfToken,
            'slug' => 'INVALID SLUG',
            'name' => 'Bad Slug',
        ], [], []);
        $res = new Response();
        $result = $this->controller->create($req, $res);
        $this->assertSame(302, $result->getStatus());

        Session::start();
        $errors = Session::pullFlash('errors');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('slug', $errors);
    }

    public function test_create_missing_name(): void
    {
        $req = new Request('POST', '/admin/custom-endpoints', [], [
            '_csrf' => $this->csrfToken,
            'slug' => 'missing-name',
        ], [], []);
        $res = new Response();
        $result = $this->controller->create($req, $res);
        $this->assertSame(302, $result->getStatus());

        Session::start();
        $errors = Session::pullFlash('errors');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('name', $errors);
    }

    public function test_edit_returns_200(): void
    {
        $id = $this->createCustomEndpoint('edit-test-' . time());
        $req = new Request('GET', "/admin/custom-endpoints/{$id}/edit", [], [], [], [], ['id' => (string) $id]);
        $res = new Response();
        $result = $this->controller->edit($req, $res);
        $this->assertSame(200, $result->getStatus());
    }

    public function test_update_valid(): void
    {
        $id = $this->createCustomEndpoint('update-test-' . time());
        $req = new Request('POST', "/admin/custom-endpoints/{$id}", [], [
            '_csrf' => $this->csrfToken,
            'slug' => 'updated-slug-' . time(),
            'name' => 'Updated Name',
        ], [], [], ['id' => (string) $id]);
        $res = new Response();
        $result = $this->controller->update($req, $res);
        $this->assertSame(302, $result->getStatus());

        $found = CustomEndpoint::find($id);
        $this->assertSame('Updated Name', $found['name']);
    }
}
