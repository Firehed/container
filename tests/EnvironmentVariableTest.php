<?php

declare(strict_types=1);

namespace Firehed\Container;

use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnvironmentVariable::class)]
class EnvironmentVariableTest extends TestCase
{
    public function testImplementsDefinitionInterface(): void
    {
        $env = new EnvironmentVariable('FOO');
        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertInstanceOf(DefinitionInterface::class, $env);
    }

    public function testImplementsCastableValueInterface(): void
    {
        $env = new EnvironmentVariable('FOO');
        // @phpstan-ignore staticMethod.alreadyNarrowedType
        self::assertInstanceOf(CastableValueInterface::class, $env, 'Env vars should support casting');
    }

    public function testIsCacheableReturnsTrue(): void
    {
        $env = new EnvironmentVariable('FOO');
        $this->assertTrue($env->isCacheable());
    }

    public function testGetDependenciesReturnsEmptyArray(): void
    {
        $env = new EnvironmentVariable('FOO');
        $this->assertSame([], $env->getDependencies());
    }

    public function testResolveReturnsEnvValue(): void
    {
        $envReader = $this->createMock(EnvReader::class);
        $envReader->expects($this->once())
            ->method('read')
            ->with('FOO')
            ->willReturn('bar');

        $env = new EnvironmentVariable('FOO');
        $result = $env->resolve(self::createStub(TypedContainerInterface::class), $envReader);
        $this->assertSame('bar', $result);
    }

    public function testResolveReturnsDefaultWhenNotSet(): void
    {
        $envReader = $this->createMock(EnvReader::class);
        $envReader->expects($this->once())
            ->method('read')
            ->with('FOO')
            ->willReturn(null);

        $env = new EnvironmentVariable('FOO', 'default_value');
        $result = $env->resolve(self::createStub(TypedContainerInterface::class), $envReader);
        $this->assertSame('default_value', $result);
    }

    public function testResolveReturnsNullDefaultWhenNotSet(): void
    {
        $envReader = $this->createMock(EnvReader::class);
        $envReader->expects($this->once())
            ->method('read')
            ->with('FOO')
            ->willReturn(null);

        $env = new EnvironmentVariable('FOO', null);
        $result = $env->resolve(self::createStub(TypedContainerInterface::class), $envReader);
        $this->assertNull($result);
    }

    public function testResolveThrowsWhenNotSetAndNoDefault(): void
    {
        $envReader = $this->createMock(EnvReader::class);
        $envReader->expects($this->once())
            ->method('read')
            ->with('FOO')
            ->willReturn(null);

        $env = new EnvironmentVariable('FOO');
        $this->expectException(Exceptions\EnvironmentVariableNotSet::class);
        $env->resolve(self::createStub(TypedContainerInterface::class), $envReader);
    }

    /**
     * @param Closure(EnvironmentVariable): mixed $setCast
     * @param CastableValueInterface::CAST_*|class-string<\BackedEnum> $expected
     */
    #[DataProvider('castSetters')]
    public function testCastIsRecorded(Closure $setCast, string $expected): void
    {
        $env = new EnvironmentVariable('FOO');
        $setCast($env);
        self::assertSame($expected, $env->getCast(), 'Requested cast should be recorded for the container');
    }

    /**
     * @return array<string, array{
     *   Closure(EnvironmentVariable): mixed,
     *   CastableValueInterface::CAST_*|class-string<\BackedEnum>,
     * }>
     */
    public static function castSetters(): array
    {
        return [
            'none' => [fn (EnvironmentVariable $e) => $e, CastableValueInterface::CAST_NONE],
            'bool' => [fn (EnvironmentVariable $e) => $e->asBool(), CastableValueInterface::CAST_BOOL],
            'int' => [fn (EnvironmentVariable $e) => $e->asInt(), CastableValueInterface::CAST_INT],
            'float' => [fn (EnvironmentVariable $e) => $e->asFloat(), CastableValueInterface::CAST_FLOAT],
            'enum' => [
                fn (EnvironmentVariable $e) => $e->asEnum(Fixtures\Environment::class),
                Fixtures\Environment::class,
            ],
        ];
    }

    public function testResolveDoesNotCast(): void
    {
        $envReader = self::createStub(EnvReader::class);
        $envReader->method('read')->willReturn('42');

        $env = new EnvironmentVariable('FOO');
        $env->asInt();
        $result = $env->resolve(self::createStub(TypedContainerInterface::class), $envReader);
        self::assertSame('42', $result, 'Casting is applied by the container, not the definition');
    }

    /** @return array<string, array{string, bool}> */
    public static function boolCastingProvider(): array
    {
        return [
            'string true' => ['true', true],
            'string 1' => ['1', true],
            'string false' => ['false', false],
            'string 0' => ['0', false],
            'empty string' => ['', false],
        ];
    }

    #[DataProvider('boolCastingProvider')]
    public function testParseBool(string $value, bool $expected): void
    {
        $this->assertSame($expected, EnvironmentVariable::parseBool($value));
    }

    public function testParseBoolThrowsOnInvalidValue(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Invalid boolean value');
        EnvironmentVariable::parseBool('invalid');
    }

    public function testGenerateCodeContainsEnvReaderCall(): void
    {
        $env = new EnvironmentVariable('MY_VAR');
        $code = $env->generateCode();
        $this->assertStringContainsString("\$this->envReader->read('MY_VAR')", $code);
    }

    public function testGenerateCodeContainsReturnStatement(): void
    {
        $env = new EnvironmentVariable('MY_VAR');
        $code = $env->generateCode();
        $this->assertStringContainsString('return', $code);
    }

    public function testGenerateCodeWithDefaultContainsDefault(): void
    {
        $env = new EnvironmentVariable('MY_VAR', 'fallback');
        $code = $env->generateCode();
        $this->assertStringContainsString('fallback', $code);
    }

    public function testGenerateCodeWithoutDefaultContainsException(): void
    {
        $env = new EnvironmentVariable('MY_VAR');
        $code = $env->generateCode();
        $this->assertStringContainsString('EnvironmentVariableNotSet', $code);
    }

    public function testGenerateCodeDoesNotCast(): void
    {
        $env = new EnvironmentVariable('MY_VAR');
        $env->asBool();
        $code = $env->generateCode();
        self::assertStringNotContainsString('parseBool', $code, 'Casting is applied by the container');
    }
}
