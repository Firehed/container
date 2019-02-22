<?php
declare(strict_types=1);

namespace Firehed\Container;

class BuilderTest extends \PHPUnit\Framework\TestCase
{
    use ContainerBuilderTestTrait;

    public function setUp(): void
    {
        $defFile1 = __DIR__ . '/ContainerTestDefinitionFile1.php';
        $defFile2 = __DIR__ . '/ContainerTestDefinitionFile2.php';

        $builder = new Builder();
        $builder->addFile($defFile1);
        $builder->addFile($defFile2);
        $this->container = $builder->build();
    }
}
