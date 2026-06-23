<?php
declare(strict_types=1);

namespace IMF\Tests\Unit\Core;

use App\Core\Session;
use IMF\Tests\Support\AppTestCase;

/**
 * Tier 1 — Pure unit tests for Session wrapper.
 *
 * @covers \App\Core\Session
 */
class SessionTest extends AppTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Session::start();
        // Clear any residual data
        foreach (['_csrf_token', 'test_key', '_flash'] as $k) {
            Session::forget($k);
        }
    }

    public function test_get_returns_default_for_missing_key(): void
    {
        $this->assertNull(Session::get('nonexistent'));
        $this->assertSame('fallback', Session::get('nonexistent', 'fallback'));
    }

    public function test_put_and_get_round_trip(): void
    {
        Session::put('test_key', 'test_value');
        $this->assertSame('test_value', Session::get('test_key'));
    }

    public function test_forget_removes_key(): void
    {
        Session::put('test_key', 'value');
        Session::forget('test_key');
        $this->assertNull(Session::get('test_key'));
    }

    public function test_flash_and_pull_flash_round_trip(): void
    {
        Session::flash('notice', 'Hello, world!');
        $this->assertSame('Hello, world!', Session::pullFlash('notice'));
    }

    public function test_pull_flash_removes_after_read(): void
    {
        Session::flash('notice', 'One-time');
        Session::pullFlash('notice');
        $this->assertNull(Session::pullFlash('notice'));
    }

    public function test_flash_accepts_array(): void
    {
        $data = ['name' => 'Alice', 'email' => 'alice@test.com'];
        Session::flash('_old', $data);
        $retrieved = Session::pullFlash('_old');
        $this->assertIsArray($retrieved);
        $this->assertSame('Alice', $retrieved['name']);
    }

    public function test_pull_flash_returns_null_for_missing(): void
    {
        $this->assertNull(Session::pullFlash('nonexistent'));
    }

    public function test_regenerate_preserves_data(): void
    {
        Session::put('test_key', 'preserved');
        Session::regenerate();
        $this->assertSame('preserved', Session::get('test_key'));
    }
}
