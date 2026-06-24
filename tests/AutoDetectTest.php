<?php

declare(strict_types=1);

namespace Firehed\Container;

use LogicException;
use RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AutoDetect::class)]
#[Small]
class AutoDetectTest extends TestCase
{
    public function setUp(): void
    {
        $_ENV['ENV'] = '';
        $_ENV['ENVIRONMENT'] = '';
    }

    public function testEmptyDirectoryIsError(): void
    {
        $_ENV['ENV'] = 'development';
        self::expectException(LogicException::class);
        self::expectExceptionMessage('Directory must not be empty');
        // @phpstan-ignore argument.type (Explicitly testing the guard)
        AutoDetect::from('');
    }

    public function testNoEnv(): void
    {
        assert($_ENV['ENVIRONMENT'] === '');
        assert($_ENV['ENV'] === '');
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Could not detect environment name');
        AutoDetect::from('./tests/ValidDefinitions/OldPhpSafe');
    }

    public function testDirectoryWithNoFiles(): void
    {
        assert($_ENV['ENVIRONMENT'] === '');
        assert($_ENV['ENV'] === '');
        $_ENV['ENV'] = 'development';
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('No config files');
        AutoDetect::from('./github');
    }

    /**
     * @param class-string<TypedContainerInterface> $expected
     */
    #[DataProvider('from')]
    public function testOk(string $env, string $expected): void
    {
        $_ENV['ENVIRONMENT'] = $env;
        $c = AutoDetect::from('./tests/ValidDefinitions/OldPhpSafe');
        self::assertInstanceOf($expected, $c);
        $c2 = AutoDetect::from('./tests/ValidDefinitions/OldPhpSafe');
        self::assertNotSame($c, $c2, 'Should not be same instance');
    }

    /**
     * @param class-string<TypedContainerInterface> $expected
     */
    #[DataProvider('from')]
    #[RunInSeparateProcess]
    public function testInstance(string $env, string $expected): void
    {
        $_ENV['ENVIRONMENT'] = $env;
        $c = AutoDetect::instance('./tests/ValidDefinitions/OldPhpSafe');
        $c2 = AutoDetect::instance('./tests/ValidDefinitions/OldPhpSafe');
        self::assertInstanceOf($expected, $c);
        self::assertSame($c, $c2);
    }

    #[RunInSeparateProcess]
    public function testInstanceMisuse(): void
    {
        $_ENV['ENVIRONMENT'] = 'whatever';
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

    public function testGetBuilderNoEnv(): void
    {
        assert($_ENV['ENVIRONMENT'] === '');
        assert($_ENV['ENV'] === '');
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Could not detect environment name');
        AutoDetect::getBuilder();
    }

    /**
     * @param class-string<BuilderInterface> $expected
     */
    #[DataProvider('getBuilderProvider')]
    public function testGetBuilderReturnsCorrectType(string $env, string $expected): void
    {
        $_ENV['ENVIRONMENT'] = $env;
        $builder = AutoDetect::getBuilder();
        self::assertInstanceOf($expected, $builder, 'getBuilder should return correct type based on environment');
    }

    public function testGetBuilderReturnsFreshInstances(): void
    {
        $_ENV['ENVIRONMENT'] = 'dev';
        $builder1 = AutoDetect::getBuilder();
        $builder2 = AutoDetect::getBuilder();
        self::assertNotSame($builder1, $builder2, 'getBuilder should return fresh instances');
    }

    /**
     * @return array{string, class-string<BuilderInterface>}[]
     * @codeCoverageIgnore
     */
    public static function getBuilderProvider(): array
    {
        return [
            ['local', Builder::class],
            ['dev', Builder::class],
            ['development', Builder::class],
            ['staging', Compiler::class],
            ['prod', Compiler::class],
            ['production', Compiler::class],
        ];
    }
}
