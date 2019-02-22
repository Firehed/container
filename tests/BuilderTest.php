<?php
declare(strict_types=1);

namespace Firehed\Container;

/**
 * @coversDefaultClass Firehed\Container\Builder
 * @covers ::<protected>
 * @covers ::<private>
 */
class BuilderTest extends \PHPUnit\Framework\TestCase
{
    use ContainerBuilderTestTrait;

    protected function getBuilder(): BuilderInterface
    {
        return new Builder();
    }
}
