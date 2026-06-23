<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Models;

use App\Models\CustomEndpoint;
use IMF\Tests\Support\DatabaseTestCase;

/**
 * Tier 2 — DB-backed tests for CustomEndpoint model.
 *
 * @covers \App\Models\CustomEndpoint
 */
class CustomEndpointTest extends DatabaseTestCase
{
    public function test_create_and_find(): void
    {
        $id = CustomEndpoint::create(
            'Test Endpoint',
            'test-endpoint-' . time(),
            null,
            '[]',
            '["pending"]'
        );

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $endpoint = CustomEndpoint::find($id);
        $this->assertIsArray($endpoint);
        $this->assertSame('Test Endpoint', $endpoint['name']);
    }

    public function test_find_by_slug(): void
    {
        $slug = 'find-by-slug-' . time();
        $id = CustomEndpoint::create('Slug Test', $slug, null, '[]', '["pending"]');

        $found = CustomEndpoint::findBySlug($slug);
        $this->assertIsArray($found);
        $this->assertSame($id, (int) $found['id']);
    }

    public function test_find_by_slug_returns_null(): void
    {
        $this->assertNull(CustomEndpoint::findBySlug('nonexistent-slug-999'));
    }

    public function test_update(): void
    {
        $id = CustomEndpoint::create(
            'Before Update',
            'before-update-' . time(),
            null,
            '[]',
            '["pending"]'
        );

        CustomEndpoint::update($id, 'After Update', 'after-update', null, '[]', '["pending"]');

        $endpoint = CustomEndpoint::find($id);
        $this->assertSame('After Update', $endpoint['name']);
    }

    public function test_list_all_returns_endpoints(): void
    {
        CustomEndpoint::create('List Test 1', 'list-test-1-' . time(), null, '[]', '["pending"]');
        CustomEndpoint::create('List Test 2', 'list-test-2-' . time(), null, '[]', '["pending"]');

        $all = CustomEndpoint::all();
        $this->assertIsArray($all);
        $this->assertGreaterThanOrEqual(2, count($all));
    }

    public function test_create_accepts_empty_fields(): void
    {
        $id = CustomEndpoint::create('Empty Fields', 'empty-fields-' . time(), null, '[]', '["pending"]');
        $endpoint = CustomEndpoint::find($id);
        $this->assertSame([], $endpoint['fields']);
    }

    public function test_create_rejects_object_fields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        // json_decode '{"a":1}' with true → ['a' => 1], which is NOT a list
        CustomEndpoint::create('Bad Fields', 'bad-fields-' . time(), null, '{"a":1}', '["pending"]');
    }
}
