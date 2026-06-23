<?php
declare(strict_types=1);

namespace IMF\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

final class SanitizeFieldValueTest extends TestCase
{
    /** @dataProvider provideSanitizeCases */
    public function test_sanitize_field_value($input, string $type, $expected): void
    {
        $result = \imf_sanitize_field_value($input, $type);
        $this->assertSame($expected, $result);
    }

    public static function provideSanitizeCases(): array
    {
        return [
            'text strips tags'            => ['<b>hello</b>',   'text',    'hello'],
            'text strips script tags'     => ['<script>alert(1)</script>', 'text', 'alert(1)'],
            'text trims whitespace'       => ['  spaces  ',     'text',    'spaces'],
            'text with int coerces'       => [123,              'text',    '123'],
            'textarea preserves tags'     => ['<p>line1</p><p>line2</p>', 'textarea', '<p>line1</p><p>line2</p>'],
            'textarea strips scripts'     => ['<p>ok</p><script>bad</script>', 'textarea', '<p>ok</p>'],
            'email lowercases'            => ['User@Example.com', 'email',    'user@example.com'],
            'email invalid returns empty' => ['not-email',      'email',    ''],
            'email empty returns empty'   => ['',               'email',    ''],
            'array recurses'              => [['<b>a</b>', '<i>b</i>'], 'text', ['a', 'b']],
            'null coerces to empty'       => [null,             'text',    ''],
        ];
    }
}
