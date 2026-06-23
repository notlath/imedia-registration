<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Models;

use App\Models\StatusHistory;
use IMF\Tests\Support\DatabaseTestCase;

/**
 * Tier 2 — DB-backed tests for StatusHistory model.
 *
 * @covers \App\Models\StatusHistory
 */
class StatusHistoryTest extends DatabaseTestCase
{
    public function test_log_creates_entry(): void
    {
        StatusHistory::log(
            'registration',
            999,
            'status',
            null,
            'pending',
            null,
            'Test entry'
        );

        $history = StatusHistory::forEntity('registration', 999);
        $this->assertCount(1, $history);
        $this->assertSame('pending', $history[0]['new_value']);
        $this->assertSame('Test entry', $history[0]['note']);
    }

    public function test_forEntity_returns_ordered_results(): void
    {
        StatusHistory::log('registration', 998, 'status', null, 'pending', null, 'First');
        StatusHistory::log('registration', 998, 'status', 'pending', 'confirm', null, 'Second');

        $history = StatusHistory::forEntity('registration', 998);
        $this->assertCount(2, $history);
        // Ordered by changed_at DESC, id DESC
        $this->assertSame('confirm', $history[0]['new_value']);
        $this->assertSame('pending', $history[1]['new_value']);
    }

    public function test_forEntity_returns_empty_array_for_nonexistent(): void
    {
        $this->assertSame([], StatusHistory::forEntity('registration', 999999));
    }
}
