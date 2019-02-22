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

    public function setUp(): void
    {
        $builder = new Builder();
        foreach ($this->getDefinitionFiles() as $file) {
            $builder->addFile($file);
        }
        $this->container = $builder->build();
    }
}
