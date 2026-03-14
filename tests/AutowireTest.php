<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionParameter;

/**
 * @covers Firehed\Container\Autowire
 */
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

    public function testOptionalParameterIsAutowirable(): void
    {
        $param = $this->getConstructorParam(Fixtures\OptionalScalarParam::class, 'param');
        self::assertTrue(Autowire::isParameterAutowirable($param));
    }

    public function testRequiredObjectParameterIsAutowirable(): void
    {
        $param = $this->getConstructorParam(Fixtures\SessionHandler::class, 'id');
        self::assertTrue(Autowire::isParameterAutowirable($param));
    }

    public function testRequiredScalarParameterIsNotAutowirable(): void
    {
        $param = $this->getConstructorParam(Fixtures\ConstructorScalar::class, 'string');
        self::assertFalse(Autowire::isParameterAutowirable($param));
    }

    public function testUntypedParameterIsNotAutowirable(): void
    {
        $param = $this->getConstructorParam(Fixtures\ConstructorUntyped::class, 'var');
        self::assertFalse(Autowire::isParameterAutowirable($param));
    }

    public function testClosureParameterIsNotAutowirable(): void
    {
        $param = $this->getConstructorParam(Fixtures\RequiresClosure::class, 'callback');
        self::assertFalse(Autowire::isParameterAutowirable($param));
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
