<?php
declare(strict_types=1);

namespace Firehed\Container;

/**
 * @covers Firehed\Container\Builder
 * @covers Firehed\Container\DevContainer
 */
class BuilderTest extends \PHPUnit\Framework\TestCase
{
    use ContainerBuilderTestTrait;

    protected function getBuilder(): BuilderInterface
    {
        return new Builder();
    }
}
