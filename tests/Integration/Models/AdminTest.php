<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Models;

use App\Models\Admin;
use IMF\Tests\Support\DatabaseTestCase;

class AdminTest extends DatabaseTestCase
{
    public function test_find_by_email_returns_admin(): void
    {
        $admin = Admin::findByEmail('admin@example.com');
        $this->assertIsArray($admin);
        $this->assertSame('Admin', $admin['name']);
    }

    public function test_find_by_email_returns_null_for_unknown(): void
    {
        $this->assertNull(Admin::findByEmail('nonexistent@test.com'));
    }

    public function test_password_is_bcrypt_hashed(): void
    {
        Admin::create('Hash Test', 'hashcheck@test.com', 'myPassword', 'admin');
        $admin = Admin::findByEmail('hashcheck@test.com');
        $this->assertStringStartsWith('$2y$', $admin['password']);
        $this->assertTrue(password_verify('myPassword', $admin['password']));
    }

    public function test_create_duplicate_email_throws(): void
    {
        $this->expectException(\PDOException::class);
        Admin::create('Duplicate', 'admin@example.com', 'password', 'admin');
    }

    public function test_find_by_id(): void
    {
        $admin = Admin::find(1);
        $this->assertIsArray($admin);
        $this->assertSame('admin@example.com', $admin['email']);
    }

    public function test_delete_removes_admin(): void
    {
        $id = Admin::create('To Delete', 'todelete@test.com', 'pass', 'admin');
        Admin::delete($id);
        $this->assertNull(Admin::find($id));
    }
}
