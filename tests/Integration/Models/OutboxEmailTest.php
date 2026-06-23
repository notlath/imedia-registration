<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Models;

use App\Models\OutboxEmail;
use IMF\Tests\Support\DatabaseTestCase;

class OutboxEmailTest extends DatabaseTestCase
{
    private const CTX = ['type' => 'registration', 'entity_id' => 1];

    public function test_enqueue_creates_queued_row(): void
    {
        $id = OutboxEmail::enqueue('to@test.com', 'Subject', '<p>Body</p>', self::CTX);
        $this->assertIsInt($id);

        $queued = OutboxEmail::listByStatus('queued');
        $found = false;
        foreach ($queued as $row) {
            if ($row['id'] === $id) {
                $found = true;
                $this->assertSame('queued', $row['status']);
                break;
            }
        }
        $this->assertTrue($found, 'Enqueued email should appear in listByStatus(queued)');
    }

    public function test_mark_sent_updates_status(): void
    {
        $id = OutboxEmail::enqueue('sent@test.com', 'Sent', '<p>Body</p>', self::CTX);
        OutboxEmail::markSent($id);

        $queued = OutboxEmail::listByStatus('queued');
        $this->assertCount(
            0,
            array_filter($queued, fn($r) => $r['id'] === $id),
            'Sent email should not appear in queued'
        );
    }

    public function test_mark_failed_terminal(): void
    {
        $id = OutboxEmail::enqueue('fail@test.com', 'Fail', '<p>Body</p>', self::CTX);
        OutboxEmail::markFailed($id, 'Connection refused', true);

        $failed = OutboxEmail::listByStatus('failed');
        $found = false;
        foreach ($failed as $row) {
            if ($row['id'] === $id) {
                $found = true;
                $this->assertStringContainsString('Connection refused', $row['last_error'] ?? '');
                break;
            }
        }
        $this->assertTrue($found, 'Terminally-failed email should appear in listByStatus(failed)');
    }

    public function test_retry_resets_failed_to_queued(): void
    {
        $id = OutboxEmail::enqueue('retry@test.com', 'Retry', '<p>Body</p>', self::CTX);
        OutboxEmail::markFailed($id, 'Terminal error', true);
        OutboxEmail::retry($id);

        $queued = OutboxEmail::listByStatus('queued');
        $found = false;
        foreach ($queued as $row) {
            if ($row['id'] === $id) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Retried email should be queued');
    }
}
