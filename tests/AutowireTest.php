<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\TestCase;

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
}
