<?php

declare(strict_types=1);

namespace GSteel\GoateeTest;

use GSteel\Goatee\Context;
use GSteel\Goatee\VariableNotFound;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function json_decode;

final class ContextTest extends TestCase
{
    /** @return list<array{0: string, 1: string, 2: mixed}> */
    public static function extractProvider(): array
    {
        return [
            ['a.b.c', '{"a":{"b":{"c":"fred"}}}',         'fred'],
            ['a.z',   '{"a":{"b":{"c":"fred"}, "z": 1}}', 1],
            ['a.z',   '{}',                               null],
            ['foo',   '{"a":{"b":{"c":"fred"}}}',         null],
            ['c',     '{"a":{"b":{"c":"fred"}}}',         null],
        ];
    }

    #[DataProvider('extractProvider')]
    public function testExtractFromArray(string $name, string $json, mixed $expect): void
    {
        $model = json_decode($json, true);
        self::assertIsArray($model);

        $context = new Context($model);
        self::assertSame($expect, $context->extract($name));
    }

    #[DataProvider('extractProvider')]
    public function testExtractFromObject(string $name, string $json, mixed $expect): void
    {
        $model = json_decode($json);
        self::assertIsObject($model);

        $context = new Context($model);
        self::assertSame($expect, $context->extract($name));
    }

    /** @return list<array{0: string, 1: string, 2: string}> */
    public static function strictVarsProvider(): array
    {
        return [
            ['a.b.c', '{}',                  'The variable "a.b.c" could not be found at position "a"'],
            ['a',     '{}',                  'A variable with the name "a" cannot be found in the data model'],
            ['a.b',   '{"a": {}}',           'The variable "a.b" could not be found at position "b"'],
            ['a.b.c', '{"a": {"b": false}}', 'The variable "a.b.c" could not be found at position "c"'],
        ];
    }

    #[DataProvider('strictVarsProvider')]
    public function testExceptionThrownAccessingNonExistentVariableWhenStrictVariablesIsEnabled(
        string $name,
        string $json,
        string $expectError,
    ): void {
        $model = json_decode($json, true);
        self::assertIsArray($model);

        $context = new Context($model, true);
        $this->expectException(VariableNotFound::class);
        $this->expectExceptionMessageIsOrContains($expectError);

        $context->extract($name);
    }
}
