<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionParameter;

#[CoversClass(Autowire::class)]
#[CoversClass(ConstructorArgument::class)]
#[CoversClass(Exceptions\NonPublicConstructor::class)]
#[CoversClass(Exceptions\VariadicParameter::class)]
class AutowireTest extends TestCase
{
    public function testClassWithNoConstructorIsEligible(): void
    {
        self::assertTrue(Autowire::isEligible(Fixtures\NoConstructorFactory::class));
    }

    public function testClassWithObjectDependencyIsEligible(): void
    {
        self::assertTrue(Autowire::isEligible(Fixtures\SessionHandler::class));
    }

    public function testClassWithOptionalScalarIsEligible(): void
    {
        self::assertTrue(Autowire::isEligible(Fixtures\OptionalScalarParam::class));
    }

    public function testClassWithRequiredScalarIsNotEligible(): void
    {
        self::assertFalse(Autowire::isEligible(Fixtures\ConstructorScalar::class));
    }

    public function testClassWithUntypedParamIsNotEligible(): void
    {
        self::assertFalse(Autowire::isEligible(Fixtures\ConstructorUntyped::class));
    }

    public function testInterfaceIsNotEligible(): void
    {
        self::assertFalse(Autowire::isEligible(Fixtures\EmptyInterface::class));
    }

    public function testEnumIsNotEligible(): void
    {
        self::assertFalse(Autowire::isEligible(Fixtures\Environment::class));
    }

    public function testClassWithClosureParamIsNotEligible(): void
    {
        self::assertFalse(Autowire::isEligible(Fixtures\RequiresClosure::class));
    }

    public function testClassWithNoTypeHintIsNotEligible(): void
    {
        self::assertFalse(Autowire::isEligible(Fixtures\NoTypeHint::class));
    }

    public function testClassWithUnionTypeParamIsNotEligible(): void
    {
        self::assertFalse(Autowire::isEligible(Fixtures\UnionTypeParam::class));
    }

    public function testClassWithPrivateConstructorIsNotEligible(): void
    {
        self::assertFalse(Autowire::isEligible(Fixtures\PrivateConstructor::class));
    }

    public function testClassWithVariadicParamIsNotEligible(): void
    {
        self::assertFalse(
            Autowire::isEligible(Fixtures\VariadicParam::class),
            'Classes with variadic constructors must be wired explicitly',
        );
    }

    public function testGetOptionalDependencyTypeThrowsForVariadic(): void
    {
        $param = $this->getConstructorParam(Fixtures\VariadicParam::class, 'ids');
        $this->expectException(Exceptions\VariadicParameter::class);
        Autowire::getOptionalDependencyType($param, Fixtures\VariadicParam::class);
    }

    /** @return array<string, array{class-string, ConstructorArgument[]}> */
    public static function constructorArgumentCases(): array
    {
        return [
            'no constructor' => [Fixtures\NoConstructorFactory::class, []],
            'required object' => [
                Fixtures\SessionHandler::class,
                [new ConstructorArgument('id', \SessionIdInterface::class, true)],
            ],
            'optional object' => [
                Fixtures\OptionalObjectParam::class,
                [new ConstructorArgument('sessionId', \SessionIdInterface::class, false)],
            ],
            'optional scalar' => [Fixtures\OptionalScalarParam::class, []],
        ];
    }

    /**
     * @param class-string $class
     * @param ConstructorArgument[] $expected
     */
    #[DataProvider('constructorArgumentCases')]
    public function testGetConstructorArguments(string $class, array $expected): void
    {
        self::assertEquals(
            $expected,
            Autowire::getConstructorArguments($class),
            'Only params the container may fill are listed; others are left to PHP',
        );
    }

    public function testGetConstructorArgumentsThrowsForNonPublicConstructor(): void
    {
        $this->expectException(Exceptions\NonPublicConstructor::class);
        Autowire::getConstructorArguments(Fixtures\PrivateConstructor::class);
    }

    public function testGetRequiredDependencyTypeReturnsTypeName(): void
    {
        $param = $this->getConstructorParam(Fixtures\SessionHandler::class, 'id');
        $type = Autowire::getRequiredDependencyType($param, Fixtures\SessionHandler::class);
        self::assertSame(\SessionIdInterface::class, $type);
    }

    public function testGetRequiredDependencyTypeThrowsForUntyped(): void
    {
        $param = $this->getConstructorParam(Fixtures\ConstructorUntyped::class, 'var');
        $this->expectException(Exceptions\UntypedValue::class);
        Autowire::getRequiredDependencyType($param, Fixtures\ConstructorUntyped::class);
    }

    public function testGetRequiredDependencyTypeThrowsForScalar(): void
    {
        $param = $this->getConstructorParam(Fixtures\ConstructorScalar::class, 'string');
        $this->expectException(Exceptions\UntypedValue::class);
        Autowire::getRequiredDependencyType($param, Fixtures\ConstructorScalar::class);
    }

    public function testGetRequiredDependencyTypeThrowsForClosure(): void
    {
        $param = $this->getConstructorParam(Fixtures\RequiresClosure::class, 'callback');
        $this->expectException(Exceptions\UntypedValue::class);
        Autowire::getRequiredDependencyType($param, Fixtures\RequiresClosure::class);
    }

    public function testGetRequiredDependencyTypeThrowsForNoTypeHint(): void
    {
        $param = $this->getConstructorParam(Fixtures\NoTypeHint::class, 'value');
        $this->expectException(Exceptions\UntypedValue::class);
        Autowire::getRequiredDependencyType($param, Fixtures\NoTypeHint::class);
    }

    public function testGetRequiredDependencyTypeThrowsForUnionType(): void
    {
        $param = $this->getConstructorParam(Fixtures\UnionTypeParam::class, 'id');
        $this->expectException(Exceptions\UntypedValue::class);
        Autowire::getRequiredDependencyType($param, Fixtures\UnionTypeParam::class);
    }

    public function testGetOptionalDependencyTypeReturnsTypeForObjectParam(): void
    {
        $param = $this->getConstructorParam(Fixtures\OptionalObjectParam::class, 'sessionId');
        $type = Autowire::getOptionalDependencyType($param, Fixtures\OptionalObjectParam::class);
        self::assertSame(\SessionIdInterface::class, $type);
    }

    public function testGetOptionalDependencyTypeReturnsNullForScalarParam(): void
    {
        $param = $this->getConstructorParam(Fixtures\OptionalScalarParam::class, 'param');
        $type = Autowire::getOptionalDependencyType($param, Fixtures\OptionalScalarParam::class);
        self::assertNull($type);
    }

    /**
     * @param class-string $class
     */
    private function getConstructorParam(string $class, string $paramName): ReflectionParameter
    {
        $rc = new ReflectionClass($class);
        $constructor = $rc->getMethod('__construct');
        foreach ($constructor->getParameters() as $param) {
            if ($param->getName() === $paramName) {
                return $param;
            }
        }
        throw new \RuntimeException("Parameter $paramName not found in $class");
    }
}
