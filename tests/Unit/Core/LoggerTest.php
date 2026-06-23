<?php
declare(strict_types=1);

namespace IMF\Tests\Unit\Core;

use App\Core\Logger;
use IMF\Tests\Support\AppTestCase;

/**
 * Tier 1 — Pure unit tests for Logger.
 *
 * @covers \App\Core\Logger
 */
class LoggerTest extends AppTestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logFile = dirname(__DIR__, 3) . '/storage/logs/app.log';
        // Ensure the file exists
        touch($this->logFile);
    }

    protected function tearDown(): void
    {
        // Truncate log after each test
        file_put_contents($this->logFile, '');
        parent::tearDown();
    }

    public function test_info_writes_log_line(): void
    {
        Logger::info('test.message', ['key' => 'value']);
        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('test.message', $contents);
        $this->assertStringContainsString('key="value"', $contents);
    }

    public function test_warning_writes_log_line(): void
    {
        Logger::warning('test.warning', ['code' => 123]);
        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('[WARNING]', $contents);
        $this->assertStringContainsString('test.warning', $contents);
    }

    public function test_error_writes_log_line(): void
    {
        Logger::error('test.error', ['msg' => 'Something broke']);
        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('[ERROR]', $contents);
        $this->assertStringContainsString('test.error', $contents);
    }

    public function test_log_line_includes_timestamp(): void
    {
        Logger::info('timestamp.test');
        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString(date('Y-m-d'), $contents);
    }
}
