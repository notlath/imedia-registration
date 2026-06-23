<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Models;

use App\Models\Registration;
use IMF\Tests\Support\DatabaseTestCase;

/**
 * Tier 2 — DB-backed tests for the Registration model.
 *
 * @covers \App\Models\Registration
 */
class RegistrationTest extends DatabaseTestCase
{
    public function test_insert_and_find(): void
    {
        $id = Registration::insert([
            'name'       => 'Test User',
            'email'      => 'test@example.com',
            'course'     => 'PHP Unit Testing',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-08-01',
            'status'     => 'pending',
        ]);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $row = Registration::find($id);
        $this->assertIsArray($row);
        $this->assertSame('Test User', $row['name']);
        $this->assertSame('test@example.com', $row['email']);
        $this->assertSame('PHP Unit Testing', $row['course']);
    }

    public function test_find_returns_null_for_missing(): void
    {
        $this->assertNull(Registration::find(999999));
    }

    public function test_update_updates_fields(): void
    {
        $id = Registration::insert([
            'name'       => 'Original',
            'email'      => 'orig@test.com',
            'course'     => 'Course A',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-08-01',
        ]);

        Registration::update($id, ['name' => 'Updated Name', 'status' => 'confirm']);

        $row = Registration::find($id);
        $this->assertSame('Updated Name', $row['name']);
        $this->assertSame('confirm', $row['status']);
    }

    public function test_insert_stores_dynamic_data(): void
    {
        $dyn = ['extra_field' => 'custom value', 'tags' => ['a', 'b']];
        $id = Registration::insert([
            'name'         => 'Dynamic Test',
            'email'        => 'dyn@test.com',
            'course'       => 'Course B',
            'start_date'   => '2026-07-01',
            'end_date'     => '2026-08-01',
            'dynamic_data' => $dyn,
        ]);

        $row = Registration::find($id);
        $this->assertIsArray($row['dynamic_data']);
        $this->assertSame('custom value', $row['dynamic_data']['extra_field']);
    }

    public function test_soft_delete_and_restore(): void
    {
        $id = Registration::insert([
            'name'       => 'To Delete',
            'email'      => 'delete@test.com',
            'course'     => 'Course C',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-08-01',
        ]);

        Registration::softDelete($id);
        $row = Registration::find($id);
        $this->assertNotNull($row['deleted_at']);

        Registration::restore($id, 'pending');
        $row = Registration::find($id);
        $this->assertNull($row['deleted_at']);
    }

    public function test_all_for_export_returns_generator(): void
    {
        $rows = Registration::allForExport();
        $this->assertInstanceOf(\Generator::class, $rows);
        $all = iterator_to_array($rows);
        $this->assertGreaterThanOrEqual(1, count($all));
    }
}

