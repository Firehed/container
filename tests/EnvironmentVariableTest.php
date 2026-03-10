<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnvironmentVariable::class)]
class EnvironmentVariableTest extends TestCase
{
    private EnvReader&MockObject $envReader;
    private TypedContainerInterface&MockObject $container;

    protected function setUp(): void
    {
        $this->envReader = $this->createMock(EnvReader::class);
        $this->container = $this->createMock(TypedContainerInterface::class);
    }

    public function testImplementsDefinitionInterface(): void
    {
        $env = new EnvironmentVariable('FOO');
        $this->assertInstanceOf(DefinitionInterface::class, $env);
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
        $this->envReader->method('read')
            ->with('FOO')
            ->willReturn('bar');

        $env = new EnvironmentVariable('FOO');
        $result = $env->resolve($this->container, $this->envReader);
        $this->assertSame('bar', $result);
    }

    public function testResolveReturnsDefaultWhenNotSet(): void
    {
        $this->envReader->method('read')
            ->with('FOO')
            ->willReturn(null);

        $env = new EnvironmentVariable('FOO', 'default_value');
        $result = $env->resolve($this->container, $this->envReader);
        $this->assertSame('default_value', $result);
    }

    public function testResolveReturnsNullDefaultWhenNotSet(): void
    {
        $this->envReader->method('read')
            ->with('FOO')
            ->willReturn(null);

        $env = new EnvironmentVariable('FOO', null);
        $result = $env->resolve($this->container, $this->envReader);
        $this->assertNull($result);
    }

    public function testResolveThrowsWhenNotSetAndNoDefault(): void
    {
        $this->envReader->method('read')
            ->with('FOO')
            ->willReturn(null);

        $env = new EnvironmentVariable('FOO');
        $this->expectException(Exceptions\EnvironmentVariableNotSet::class);
        $env->resolve($this->container, $this->envReader);
    }

    #[DataProvider('boolCastingProvider')]
    public function testResolveWithBoolCasting(string $envValue, bool $expected): void
    {
        $this->envReader->method('read')->with('FOO')->willReturn($envValue);

        $env = new EnvironmentVariable('FOO');
        $env->asBool();
        $this->assertSame($expected, $env->resolve($this->container, $this->envReader));
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

    #[DataProvider('intCastingProvider')]
    public function testResolveWithIntCasting(string $envValue, int $expected): void
    {
        $this->envReader->method('read')->with('FOO')->willReturn($envValue);

        $env = new EnvironmentVariable('FOO');
        $env->asInt();
        $this->assertSame($expected, $env->resolve($this->container, $this->envReader));
    }

    /** @return array<string, array{string, int}> */
    public static function intCastingProvider(): array
    {
        return [
            'positive' => ['42', 42],
            'zero' => ['0', 0],
            'negative' => ['-5', -5],
        ];
    }

    #[DataProvider('floatCastingProvider')]
    public function testResolveWithFloatCasting(string $envValue, float $expected): void
    {
        $this->envReader->method('read')->with('FOO')->willReturn($envValue);

        $env = new EnvironmentVariable('FOO');
        $env->asFloat();
        $this->assertSame($expected, $env->resolve($this->container, $this->envReader));
    }

    /** @return array<string, array{string, float}> */
    public static function floatCastingProvider(): array
    {
        return [
            'positive' => ['3.14', 3.14],
            'zero' => ['0.0', 0.0],
            'negative' => ['-2.5', -2.5],
        ];
    }

    public function testResolveWithEnumCasting(): void
    {
        $this->envReader->method('read')
            ->with('ENV')
            ->willReturn('testing');

        $env = new EnvironmentVariable('ENV');
        $env->asEnum(Fixtures\Environment::class);
        $result = $env->resolve($this->container, $this->envReader);
        $this->assertSame(Fixtures\Environment::TESTING, $result);
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
}
