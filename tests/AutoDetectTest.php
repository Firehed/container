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
        assert(getenv('ENVIRONMENT') == false); // @phpstan-ignore equal.notAllowed
        assert(getenv('ENV') == false); // @phpstan-ignore equal.notAllowed
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Could not find an environment');
        AutoDetect::from('./tests/ValidDefinitions/OldPhpSafe');
    }

    public function testDirectoryWithNoFiles(): void
    {
        assert(getenv('ENVIRONMENT') == false); // @phpstan-ignore equal.notAllowed
        assert(getenv('ENV') == false); // @phpstan-ignore equal.notAllowed
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
        $c = AutoDetect::from('./tests/ValidDefinitions/OldPhpSafe');
        self::assertInstanceOf($expected, $c);
        $c2 = AutoDetect::from('./tests/ValidDefinitions/OldPhpSafe');
        self::assertNotSame($c, $c2, 'Should not be same instance');
    }

    /**
     * @dataProvider from
     * @param class-string<TypedContainerInterface> $expected
     * @runInSeparateProcess
     */
    public function testInstance(string $env, string $expected): void
    {
        putenv('ENVIRONMENT=' . $env);
        $c = AutoDetect::instance('./tests/ValidDefinitions/OldPhpSafe');
        $c2 = AutoDetect::instance('./tests/ValidDefinitions/OldPhpSafe');
        self::assertInstanceOf($expected, $c);
        self::assertSame($c, $c2);
    }

    /**
     * @runInSeparateProcess
     */
    public function testInstanceMisuse(): void
    {
        putenv('ENVIRONMENT=whatever');
        AutoDetect::instance('./tests/ValidDefinitions/OldPhpSafe');
        self::expectException(LogicException::class);
        self::expectExceptionMessage('Instance must receive the same directory each time');
        AutoDetect::instance('./tests/ErrorDefinitions');
    }

    /**
     * @return array{string, class-string<TypedContainerInterface>}[]
     */
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
