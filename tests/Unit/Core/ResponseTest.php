<?php
declare(strict_types=1);

namespace IMF\Tests\Unit\Core;

use App\Core\Response;
use IMF\Tests\Support\AppTestCase;

/**
 * Tier 1 — Pure unit tests for Response.
 *
 * @covers \App\Core\Response
 */
class ResponseTest extends AppTestCase
{
    public function test_view_sets_html_content_type(): void
    {
        $res = Response::make()->view('login', [], 'public');
        $this->assertStringContainsString('<!doctype html>', $res->getBody());
    }

    public function test_json_sets_content_type(): void
    {
        $res = Response::make()->json(['success' => true]);
        $body = $res->getBody();
        $decoded = json_decode($body, true);
        $this->assertTrue($decoded['success']);
    }

    public function test_json_encodes_body(): void
    {
        $res = Response::make()->json(['success' => true, 'id' => 42]);
        $body = $res->getBody();
        $decoded = json_decode($body, true);
        $this->assertSame(42, $decoded['id']);
    }

    public function test_error_returns_html_by_default(): void
    {
        $res = Response::make()->error(404, 'Not found.');
        $this->assertSame(404, $res->getStatus());
        $this->assertStringContainsString('404', $res->getBody());
    }

    public function test_text_sets_content(): void
    {
        $res = Response::make()->text('Hello', 200);
        $this->assertSame('Hello', $res->getBody());
    }

    public function test_redirect_sets_body_empty(): void
    {
        $res = Response::make()->redirect('/admin/login');
        $this->assertSame(302, $res->getStatus());
        $this->assertSame('', $res->getBody());
    }

    public function test_status_defaults_to_200(): void
    {
        $res = Response::make();
        $this->assertSame(200, $res->getStatus());
    }

    public function test_error_json_when_accept_header(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $res = Response::make()->error(401, 'Unauthorized');
        $body = $res->getBody();
        $decoded = json_decode($body, true);
        $this->assertIsArray($decoded);
        $this->assertFalse($decoded['success']);
        $this->assertSame('Unauthorized', $decoded['error']);
        unset($_SERVER['HTTP_ACCEPT']);
    }
}
