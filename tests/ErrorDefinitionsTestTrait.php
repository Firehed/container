<?php
declare(strict_types=1);

namespace Firehed\Container;

use SessionHandlerInterface;

trait ErrorDefinitionsTestTrait
{
    abstract protected function getBuilder(): BuilderInterface;

    public function testConstructorWithScalarArgErrors(): void
    {
        $builder = $this->getBuilder();
        $builder->addFile(__DIR__ . '/ErrorDefinitions/ConstructorScalar.php');
        $this->expectException(Exceptions\UntypedValue::class);
        $c = $builder->build();
        $c->get(Fixtures\ConstructorScalar::class);
    }

    public function testConstructorWithUntypedArgErrors(): void
    {
        $builder = $this->getBuilder();
        $builder->addFile(__DIR__ . '/ErrorDefinitions/ConstructorUntyped.php');
        $this->expectException(Exceptions\UntypedValue::class);
        $c = $builder->build();
        $c->get(Fixtures\ConstructorUntyped::class);
    }

    public function testConstructorWithUndefiendTypedArgErrors(): void
    {
        $builder = $this->getBuilder();
        $builder->addFile(__DIR__ . '/ErrorDefinitions/RequiredParams.php');
        $this->expectException(Exceptions\NotFound::class);
        // Make sure messages are useful - #20
        $this->expectExceptionMessage(\SessionIdInterface::class, 'NotFound should contain missing class name');
        $this->expectExceptionMessage('$id', 'NotFound should contain constructor property name');
        $c = $builder->build();
        $c->get(Fixtures\SessionHandler::class);
    }

    public function testImplicitInterfaceAutowire(): void
    {
        $builder = $this->getBuilder();
        $builder->addFile(__DIR__ . '/ErrorDefinitions/AmbiguousInterfaceImplicit.php');
        $this->expectException(Exceptions\AmbiguousMapping::class);
        $c = $builder->build();
        $c->get(SessionHandlerInterface::class);
    }

    public function testExplicitInterfaceAutowire(): void
    {
        $builder = $this->getBuilder();
        $builder->addFile(__DIR__ . '/ErrorDefinitions/AmbiguousInterfaceExplicit.php');
        $this->expectException(Exceptions\AmbiguousMapping::class);
        $c = $builder->build();
        $c->get(SessionHandlerInterface::class);
    }

    public function testInterfaceToNonClass(): void
    {
        $builder = $this->getBuilder();
        $builder->addFile(__DIR__ . '/ErrorDefinitions/InterfaceToNonClass.php');
        $this->expectException(Exceptions\InvalidClassMapping::class);
        $c = $builder->build();
        $c->get(SessionHandlerInterface::class);
    }

    public function testInterfaceFactory(): void
    {
        $builder = $this->getBuilder();
        $builder->addFile(__DIR__ . '/ErrorDefinitions/InterfaceFactory.php');
        $this->expectException(Exceptions\AmbiguousMapping::class);
        $c = $builder->build();
        $c->get(SessionHandlerInterface::class);
    }

    public function testStringFactory(): void
    {
        $builder = $this->getBuilder();
        $builder->addFile(__DIR__ . '/ErrorDefinitions/StringFactory.php');
        $this->expectException(Exceptions\AmbiguousMapping::class);
        $c = $builder->build();
        $c->get('hello');
    }
}
