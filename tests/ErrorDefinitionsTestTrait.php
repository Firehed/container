<?php
declare(strict_types=1);

namespace Firehed\Container;

trait ErrorDefinitionsTestTrait
{
    public function testConstructorWithScalarArgErrors(): void
    {
        $builder = $this->getBuilder();
        $this->expectException(Exceptions\UntypedValue::class);
        $builder->addFile(__DIR__ . '/ErrorDefinitionFile1.php');
        $c = $builder->build();
        $c->get(Fixtures\ConstructorScalar::class);
    }

    public function testConstructorWithUntypedArgErrors(): void
    {
        $builder = $this->getBuilder();
        $this->expectException(Exceptions\UntypedValue::class);
        $builder->addFile(__DIR__ . '/ErrorDefinitionFile2.php');
        $c = $builder->build();
        $c->get(Fixtures\ConstructorUntyped::class);
    }
}
