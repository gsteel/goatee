<?php

declare(strict_types=1);

namespace GSteel\GoateeTest;

use GSteel\Goatee\FunctionManager;
use GSteel\Goatee\FunctionNotFound;
use PHPUnit\Framework\TestCase;

final class FunctionManagerTest extends TestCase
{
    public function testFunctionsAreRegisteredDuringConstruct(): void
    {
        $fn = static fn (): null => null;

        $manager = new FunctionManager([
            'foo' => $fn,
        ]);

        self::assertTrue($manager->has('foo'));
        self::assertSame($fn, $manager->get('foo'));
    }

    public function testMissingFunctionsAreExceptionalOnRetrieval(): void
    {
        $manger = new FunctionManager();
        $this->expectException(FunctionNotFound::class);
        $manger->get('foo');
    }

    public function testRegistrationOfFunctions(): void
    {
        $fnA = static fn (): null => null;
        $fnB = static fn (): null => null;

        $manger = new FunctionManager();
        $manger->registerFunction('foo', $fnA);
        self::assertSame($fnA, $manger->get('foo'));

        $manger->registerFunction('foo', $fnB);
        self::assertSame($fnB, $manger->get('foo'));
        self::assertNotSame($fnA, $manger->get('foo'));
    }

    public function testFunctionRetrievalIsCaseSensitive(): void
    {
        $fn = static fn (): null => null;
        $manager = new FunctionManager([
            'foo' => $fn,
        ]);

        self::assertTrue($manager->has('foo'));
        self::assertFalse($manager->has('Foo'));
    }
}
