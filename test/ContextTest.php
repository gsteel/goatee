<?php

declare(strict_types=1);

namespace GSteel\GoateeTest;

use GSteel\Goatee\Context;
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
}
