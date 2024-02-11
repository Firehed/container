<?php

declare(strict_types=1);

namespace Firehed\Container;

use LogicException;
use RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AutoDetect::class)]
#[Small]
class AutoDetectTest extends TestCase
{
    public function setUp(): void
    {
        putenv('ENV=');
        putenv('ENVIRONMENT=');
    }

    public function testEmptyDirectoryIsError(): void
    {
        self::expectException(LogicException::class);
        self::expectExceptionMessage('Directory is empty');
        AutoDetect::from('');
    }

    public function testNoEnv(): void
    {
        assert(getenv('ENVIRONMENT') == false);
        assert(getenv('ENV') == false);
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Could not find an environment');
        AutoDetect::from('./tests/ValidDefinitions');
    }

    public function testDirectoryWithNoFiles(): void
    {
        assert(getenv('ENVIRONMENT') == false);
        assert(getenv('ENV') == false);
        putenv('ENV=development');
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('No config files');
        AutoDetect::from('./github');
    }

    /**
     * @dataProvider from
     * @param class-string<TypedContainerInterface> $expected
     */
    public function testOk(string $env, string $expected): void
    {
        putenv('ENVIRONMENT=' . $env);
        $c = AutoDetect::from('./tests/ValidDefinitions');
        self::assertInstanceOf($expected, $c);
        $c2 = AutoDetect::from('./tests/ValidDefinitions');
        self::assertNotSame($c, $c2, 'Should not be same instance');
    }

    public static function from(): array
    {
        return [
            ['local', DevContainer::class],
            ['LOCAL', DevContainer::class],
            ['dev', DevContainer::class],
            ['development', DevContainer::class],
            ['DEV', DevContainer::class],
            ['DeVeLoPmEnT', DevContainer::class],
            ['staging', CompiledContainer::class],
            ['StAgInG', CompiledContainer::class],
            ['prod', CompiledContainer::class],
            ['PrOdUcTiOn', CompiledContainer::class],
            ['whatever', CompiledContainer::class],
        ];
    }
}
