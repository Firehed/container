<?php
declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerExceptionInterface;
use SessionHandlerInterface;
use SessionIdInterface;

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

    /** @return array<string, array{string, class-string}> */
    public static function variadicDefinitions(): array
    {
        return [
            'typed, type defined' => ['VariadicParam.php', Fixtures\VariadicParam::class],
            'typed, type not defined' => ['VariadicParamTypeMissing.php', Fixtures\VariadicParam::class],
            'untyped' => ['UntypedVariadicParam.php', Fixtures\UntypedVariadicParam::class],
        ];
    }

    /** @param class-string $class */
    #[DataProvider('variadicDefinitions')]
    public function testConstructorWithVariadicArgErrors(string $file, string $class): void
    {
        $builder = $this->getBuilder();
        $builder->addFile(__DIR__ . '/ErrorDefinitions/' . $file);
        $this->expectException(Exceptions\VariadicParameter::class);
        $this->expectExceptionMessageMatches(sprintf('/`\$ids`.*`%s`/', preg_quote($class, '/')));
        $c = $builder->build();
        $c->get($class);
    }

    public function testNonPublicConstructorErrors(): void
    {
        $builder = $this->getBuilder();
        $builder->addFile(__DIR__ . '/ErrorDefinitions/PrivateConstructor.php');
        $this->expectException(Exceptions\NonPublicConstructor::class);
        $c = $builder->build();
        $c->get(Fixtures\PrivateConstructor::class);
    }

    public function testConstructorWithUndefinedTypedArgErrors(): void
    {
        $builder = $this->getBuilder();
        $builder->addFile(__DIR__ . '/ErrorDefinitions/RequiredParams.php');
        $this->expectException(Exceptions\NotFound::class);
        // Make sure messages are useful - #20
        $this->expectExceptionMessage(SessionIdInterface::class);
        $this->expectExceptionMessage(Fixtures\SessionHandler::class);
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

    public function testHandlingInternalError(): void
    {
        $builder = $this->getBuilder();
        $builder->addFile(__DIR__ . '/ErrorDefinitions/ErrorException.php');
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('api_service');
        $this->expectExceptionMessage('api_host');
        $this->expectExceptionMessage('api_url');
        $c = $builder->build();
        $c->get('api_service');
    }
}
