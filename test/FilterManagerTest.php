<?php

declare(strict_types=1);

namespace GSteel\GoateeTest;

use GSteel\Goatee\FilterManager;
use GSteel\Goatee\FilterNotFound;
use PHPUnit\Framework\TestCase;

final class FilterManagerTest extends TestCase
{
    public function testFiltersAreRegisteredDuringConstruct(): void
    {
        $fn = static fn (): null => null;

        $manager = new FilterManager([
            'foo' => $fn,
        ]);

        self::assertTrue($manager->has('foo'));
        self::assertSame($fn, $manager->get('foo'));
    }

    public function testMissingFiltersAreExceptionalOnRetrieval(): void
    {
        $manger = new FilterManager();
        $this->expectException(FilterNotFound::class);
        $manger->get('foo');
    }

    public function testRegistrationOfFilters(): void
    {
        $fnA = static fn (): null => null;
        $fnB = static fn (): null => null;

        $manger = new FilterManager();
        $manger->registerFilter('foo', $fnA);
        self::assertSame($fnA, $manger->get('foo'));

        $manger->registerFilter('foo', $fnB);
        self::assertSame($fnB, $manger->get('foo'));
        self::assertNotSame($fnA, $manger->get('foo'));
    }

    public function testFilterRetrievalIsCaseSensitive(): void
    {
        $fn = static fn (): null => null;
        $manager = new FilterManager([
            'foo' => $fn,
        ]);

        self::assertTrue($manager->has('foo'));
        self::assertFalse($manager->has('Foo'));
    }
}
