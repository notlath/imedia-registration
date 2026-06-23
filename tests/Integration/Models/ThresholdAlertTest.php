<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Models;

use App\Models\ThresholdAlert;
use IMF\Tests\Support\DatabaseTestCase;

class ThresholdAlertTest extends DatabaseTestCase
{
    public function test_was_alerted_returns_false_for_new(): void
    {
        $this->assertFalse(ThresholdAlert::wasAlerted('NonExistentCourse', 2026, 6));
    }

    public function test_record_and_alert_creates_entry(): void
    {
        $result = ThresholdAlert::recordAndAlert('Test Course', 2026, 6);
        $this->assertIsBool($result);
    }

    public function test_was_alerted_returns_true_after_record(): void
    {
        ThresholdAlert::recordAndAlert('Check Course', 2026, 6);
        $this->assertTrue(ThresholdAlert::wasAlerted('Check Course', 2026, 6));
    }

    public function test_list_sent_returns_alerts(): void
    {
        ThresholdAlert::recordAndAlert('List Course 1', 2026, 6);
        ThresholdAlert::recordAndAlert('List Course 2', 2026, 7);

        $list = ThresholdAlert::listSent();
        $this->assertIsArray($list);
        $this->assertGreaterThanOrEqual(2, count($list));
    }
}
