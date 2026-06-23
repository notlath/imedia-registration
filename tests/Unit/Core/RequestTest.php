<?php
declare(strict_types=1);

namespace IMF\Tests\Unit\Core;

use App\Core\Request;
use IMF\Tests\Support\AppTestCase;

/**
 * Tier 1 — Pure unit tests for Request.
 *
 * @covers \App\Core\Request
 */
class RequestTest extends AppTestCase
{
    public function test_input_returns_value_from_body(): void
    {
        $req = new Request('POST', '/', [], ['name' => 'Alice'], [], []);
        $this->assertSame('Alice', $req->input('name'));
    }

    public function test_input_returns_default_for_missing_key(): void
    {
        $req = new Request('GET', '/', [], [], [], []);
        $this->assertNull($req->input('missing'));
        $this->assertSame('fallback', $req->input('missing', 'fallback'));
    }

    public function test_input_returns_array_for_nested_key(): void
    {
        $req = new Request('POST', '/', [], ['reg' => ['name' => 'Alice']], [], []);
        $reg = $req->input('reg');
        $this->assertIsArray($reg);
        $this->assertSame('Alice', $reg['name']);
    }

    public function test_query_returns_value(): void
    {
        $req = new Request('GET', '/', ['page' => '2'], [], [], []);
        $this->assertSame('2', $req->query('page'));
    }

    public function test_param_returns_value(): void
    {
        $req = new Request('GET', '/users/5', [], [], [], [], ['id' => '5']);
        $this->assertSame('5', $req->param('id'));
    }

    public function test_header_is_case_insensitive(): void
    {
        $req = new Request('GET', '/', [], [], [], ['x-imf-signature' => 'sha256=abc']);
        $this->assertSame('sha256=abc', $req->header('X-IMF-Signature'));
        $this->assertSame('sha256=abc', $req->header('x-imf-signature'));
        $this->assertSame('sha256=abc', $req->header('X-IMF-SIGNATURE'));
    }

    public function test_header_returns_null_for_missing(): void
    {
        $req = new Request('GET', '/', [], [], [], []);
        $this->assertNull($req->header('x-nonexistent'));
    }

    public function test_method_is_accessible(): void
    {
        $req = new Request('POST', '/admin/reg', [], [], [], []);
        $this->assertSame('POST', $req->method);
    }

    public function test_path_is_accessible(): void
    {
        $req = new Request('GET', '/admin/registrations', [], [], [], []);
        $this->assertSame('/admin/registrations', $req->path);
    }

    public function test_file_returns_null_for_missing(): void
    {
        $req = new Request('GET', '/', [], [], [], []);
        $this->assertNull($req->file('resume'));
    }

    public function test_ip_loopback(): void
    {
        $req = new Request('GET', '/', [], [], [], []);
        $this->assertSame('', $req->ip());
    }

    public function test_raw_body(): void
    {
        $req = new Request('POST', '/', [], ['a' => 1], [], []);
        $this->assertSame(['a' => 1], $req->body);
    }
}
