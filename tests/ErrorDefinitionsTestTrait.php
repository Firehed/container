<?php
declare(strict_types=1);

namespace Firehed\Container;

trait ErrorDefinitionsTestTrait
{
    abstract protected function getBuilder(): BuilderInterface;

    public function testConstructorWithScalarArgErrors(): void
    {
        $builder = $this->getBuilder();
        $this->expectException(Exceptions\UntypedValue::class);
        $builder->addFile(__DIR__ . '/ErrorDefinitions/ConstructorScalar.php');
        $c = $builder->build();
        $c->get(Fixtures\ConstructorScalar::class);
    }

    public function testConstructorWithUntypedArgErrors(): void
    {
        $builder = $this->getBuilder();
        $this->expectException(Exceptions\UntypedValue::class);
        $builder->addFile(__DIR__ . '/ErrorDefinitions/ConstructorUntyped.php');
        $c = $builder->build();
        $c->get(Fixtures\ConstructorUntyped::class);
    }
}
