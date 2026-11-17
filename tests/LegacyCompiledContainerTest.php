<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CompiledContainer::class)]
class LegacyCompiledContainerTest extends TestCase
{
    #[DataProvider('values')]
    public function testCastsAreNotReapplied(string $key, mixed $expected): void
    {
        $container = new Fixtures\LegacyCompiledContainer(new EnvReader([]));
        self::assertSame($expected, $container->get($key), 'Previously compiled casts should be used as-is');
    }

    /** @return array<string, array{string, mixed}> */
    public static function values(): array
    {
        return [
            'bool' => ['bool', true],
            'enum' => ['enum', Fixtures\Environment::TESTING],
            'int' => ['int', 42],
        ];
    }
}
