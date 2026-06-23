<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Models;

use App\Models\Setting;
use IMF\Tests\Support\DatabaseTestCase;

/**
 * Tier 2 — DB-backed tests for the Setting model.
 *
 * @covers \App\Models\Setting
 */
class SettingTest extends DatabaseTestCase
{
    public function test_get_returns_value(): void
    {
        $secret = Setting::get('hmac_shared_secret');
        $this->assertIsString($secret);
        $this->assertNotEmpty($secret);
    }

    public function test_get_returns_default_for_missing_key(): void
    {
        $this->assertNull(Setting::get('nonexistent_column'));
        $this->assertSame('fallback', Setting::get('nonexistent_column', 'fallback'));
    }

    public function test_put_updates_value(): void
    {
        $original = Setting::get('site_name');
        Setting::put('site_name', 'Test Site');

        $this->assertSame('Test Site', Setting::get('site_name'));

        // Restore
        Setting::put('site_name', $original);
    }

    public function test_all_returns_array(): void
    {
        $all = Setting::all();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('id', $all);
        $this->assertArrayHasKey('hmac_shared_secret', $all);
    }

    public function test_refresh_reloads_from_db(): void
    {
        $all = Setting::all();
        $original = $all['site_name'];

        // Direct DB update bypassing cache
        Setting::put('site_name', 'Refreshed Site');
        $this->assertSame('Refreshed Site', Setting::get('site_name'));

        Setting::refresh();
        $this->assertSame('Refreshed Site', Setting::get('site_name'));

        // Restore
        Setting::put('site_name', $original);
    }
}
