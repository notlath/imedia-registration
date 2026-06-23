<?php
declare(strict_types=1);

namespace IMF\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

final class FormatEntryDateTest extends TestCase
{
    /** @dataProvider provideFormatDateCases */
    public function test_format_entry_date(string $input, string $expectedContains): void
    {
        $result = \imf_format_entry_date($input);
        $this->assertStringContainsString($expectedContains, $result);
    }

    public static function provideFormatDateCases(): array
    {
        return [
            'normal datetime'     => ['2024-06-15 14:30:00', 'Jun 15, 2024'],
            'another date'        => ['2023-01-01 00:00:00', 'Jan 1, 2023'],
        ];
    }

    public function test_invalid_date_returns_input(): void
    {
        $result = \imf_format_entry_date('not-a-date');
        $this->assertSame('not-a-date', $result);
    }

    public function test_empty_string_returns_current_date(): void
    {
        // PHP's DateTime('') returns current datetime, not an error.
        $result = \imf_format_entry_date('');
        $this->assertStringContainsString(date('Y'), $result);
    }
}
