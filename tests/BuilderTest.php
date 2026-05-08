<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Autowire::class)]
#[CoversClass(AutowiredClass::class)]
#[CoversClass(Builder::class)]
#[CoversClass(DevContainer::class)]
#[CoversClass(Factory::class)]
class BuilderTest extends TestCase
{
    use ContainerBuilderTestTrait;

    protected function getBuilder(): BuilderInterface
    {
        return new Builder();
    }
}
