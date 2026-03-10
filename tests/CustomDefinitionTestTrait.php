<?php

declare(strict_types=1);

namespace Firehed\Container;

/**
 * Tests for DefinitionInterface support
 */
trait CustomDefinitionTestTrait
{
    abstract public function getContainer(): TypedContainerInterface;

    public function testCustomDefinitionString(): void
    {
        $container = $this->getContainer();
        $this->assertTrue($container->has('custom_string'));
        $this->assertSame('hello from custom', $container->get('custom_string'));
    }

    public function testCustomDefinitionInt(): void
    {
        $container = $this->getContainer();
        $this->assertTrue($container->has('custom_int'));
        $this->assertSame(42, $container->get('custom_int'));
    }

    public function testCustomDefinitionArray(): void
    {
        $container = $this->getContainer();
        $this->assertTrue($container->has('custom_array'));
        $this->assertSame(['a', 'b', 'c'], $container->get('custom_array'));
    }

    public function testCustomDefinitionCacheableReturnsSameInstance(): void
    {
        $container = $this->getContainer();
        $first = $container->get('custom_cacheable');
        $second = $container->get('custom_cacheable');
        $this->assertSame($first, $second);
    }

    public function testCustomDefinitionFactoryReturnsValue(): void
    {
        $container = $this->getContainer();
        $this->assertSame('not cached', $container->get('custom_factory'));
    }
}
