<?php
declare(strict_types=1);

namespace IMF\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

final class NormalizeEntryDataTest extends TestCase
{
    /** @dataProvider provideNormalizeCases */
    public function test_normalize_entry_data($input, array $expected): void
    {
        $result = \imf_normalize_entry_data($input);
        $this->assertSame($expected, $result);
    }

    public static function provideNormalizeCases(): array
    {
        $canonical = [
            ['name' => 'email', 'label' => 'Email', 'value' => 'a@b.com', 'type' => 'email'],
        ];

        return [
            'canonical format returns as-is' => [
                $canonical,
                $canonical,
            ],
            'empty array'                    => [[], []],
            'null input'                     => [null, []],
            'string input'                   => ['invalid', []],
            'int input'                      => [42, []],
            'legacy assoc map'               => [
                ['full_name' => 'Jane Doe', 'email' => 'j@d.com'],
                [
                    ['name' => 'full_name', 'label' => 'Full Name', 'value' => 'Jane Doe', 'type' => 'text'],
                    ['name' => 'email', 'label' => 'Email', 'value' => 'j@d.com', 'type' => 'text'],
                ],
            ],
            'legacy assoc with array value'  => [
                ['tags' => ['a', 'b']],
                [
                    ['name' => 'tags', 'label' => 'Tags', 'value' => 'a, b', 'type' => 'text'],
                ],
            ],
            'legacy indexed list'            => [
                ['val1', 'val2'],
                [
                    ['name' => 'field_0', 'label' => 'Field 1', 'value' => 'val1', 'type' => 'text'],
                    ['name' => 'field_1', 'label' => 'Field 2', 'value' => 'val2', 'type' => 'text'],
                ],
            ],
            'legacy assoc with underscores'  => [
                ['first_name' => 'Jane'],
                [
                    ['name' => 'first_name', 'label' => 'First Name', 'value' => 'Jane', 'type' => 'text'],
                ],
            ],
        ];
    }

    public function test_canonical_shortcircuit_correctly(): void
    {
        $mixed = [['name' => 'field1', 'label' => 'Field 1', 'value' => 'v1', 'type' => 'text']];
        $result = \imf_normalize_entry_data($mixed);
        $this->assertSame($mixed, $result);
    }

    public function test_empty_normalized_without_name_key(): void
    {
        $result = \imf_normalize_entry_data([['not_name' => 'x']]);
        $this->assertNotEmpty($result);
    }
}
