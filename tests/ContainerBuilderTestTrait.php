<?php
declare(strict_types=1);

namespace Firehed\Container;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Container\ContainerInterface;
use SessionHandlerInterface;
use SessionIdInterface;

/**
 * This is a test trait to help ensure all processes end up with the same
 * results
 */
trait ContainerBuilderTestTrait
{
    /** @var ContainerInterface */
    private $container;

    public function setUp(): void
    {
        $builder = $this->getBuilder();
        foreach ($this->getDefinitionFiles() as $file) {
            $builder->addFile($file);
        }
        $this->container = $builder->build();
    }

    abstract protected function getBuilder(): BuilderInterface;

    private function getDefinitionFiles(): array
    {
        return [
            __DIR__ . '/ContainerTestDefinitionFile1.php',
            __DIR__ . '/ContainerTestDefinitionFile2.php',
        ];
    }

    /**
     * SomeImplementation::class => autowire()
     * where SomeImplementation has no constructor arguments
     */
    public function testAutowiredDefinition(): void
    {
        $this->assertGetSingleton(Fixtures\SessionId::class);
    }

    /**
     * SomeInterface::class => SomeImplementation::class
     * SomeImplementation::class => autowire()
     * where SomeImplementation has no constructor arguments
     */
    public function testInterfaceMapping(): void
    {
        $this->assertGetSingleton(
            SessionIdInterface::class,
            Fixtures\SessionId::class
        );
    }

    /**
     * SomeImplementation::class => autowire()
     * where SomeImplementation has >=1 constructor arguments
     */
    public function testAutowiredDefinitionWithConstuctorArg(): void
    {
        $this->assertGetSingleton(Fixtures\SessionHandler::class);
    }

    /**
     * SomeInterface::class => SomeImplementation::class
     * SomeImplementation::class => autowire()
     * where SomeImplementation has >= 1 constructor arguments
     */
    public function testInterfaceMappedToAutowiredDefinitionWithConstructorArg(): void
    {
        $this->assertGetSingleton(
            SessionHandlerInterface::class,
            Fixtures\SessionHandler::class
        );
    }

    /**
     * SomeImplementation::class => factory(function ($c) {
     *   return new SomeImplementation($c->get('param'));
     * }
     */
    public function testMultipleCallsToFactoryWithBodyReturnDifferentObjects(): void
    {
        $this->assertGetFactory(DateTime::class);
    }

    /**
     * SomeInterface::class => SomeImplementation::clas;
     * SomeImplementation::class => factory(...)
     */
    public function testMultipleCallsToInterfaceMappedToFactoryDefinitionWithBody(): void
    {
        $this->assertGetFactory(
            DateTimeInterface::class,
            DateTime::class
        );
    }

    /**
     * SomeImplementation::class => factory()
     */
    public function testMultipleCallsToFactoryWithNoBodyReturnDifferentObjects(): void
    {
        $this->assertGetFactory(Fixtures\NoConstructorFactory::class);
    }

    /**
     * SomeInterface::class => function () {
     *   return new SomeImplementation();
     * }
     */
    public function testInterfaceKeyToExplicitDefinition(): void
    {
        $this->assertGetSingleton(Fixtures\ExplicitDefinitionInterface::class);
    }

    /**
     * some_string => scalar_literal
     * @dataProvider scalarLiterals
     * @param mixed $expectedValue
     */
    public function testScalarLiteral(string $key, $expectedValue): void
    {
        $this->assertTrue($this->container->has($key));
        $value = $this->container->get($key);
        $this->assertSame($expectedValue, $value);
    }

    public function scalarLiterals(): array
    {
        return [
            ['string_literal', 'UnitTest'],
            ['int_literal', 42],
            ['float_literal', 123.45],
            ['bool_literal', true],
            ['array_literal', ['a', 'b', 'c']],
            ['dict_literal', ['a' => 1, 'b' => 2, 'c' => 3]],
        ];
    }

    private function assertGetSingleton(string $key, ?string $type = null): void
    {
        $type = $type ?? $key;
        $this->assertTrue($this->container->has($key));
        $values = [];
        for ($i = 0; $i < 3; $i++) {
            $value = $this->container->get($key);
            $this->assertInstanceOf($type, $value);
            $values[] = $value;
        }
        $this->assertAllAreSame($values);
    }

    private function assertGetFactory(string $key, ?string $type = null): void
    {
        $type = $type ?? $key;
        $this->assertTrue($this->container->has($key), "Container should have $key");
        $values = [];
        for ($i = 0; $i < 3; $i++) {
            $value = $this->container->get($key);
            $this->assertInstanceOf($type, $value);
            $values[] = $value;
        }
        $this->assertAllAreNotSame($values);
    }


    /**
     * @param mixed[] $args
     */
    private function assertAllAreSame(array $args): void
    {
        while (count($args) >= 2) {
            $first = array_shift($args);
            foreach ($args as $arg) {
                $this->assertSame($first, $arg);
            }
        }
    }

    /**
     * @param mixed[] $args
     */
    private function assertAllAreNotSame(array $args): void
    {
        while (count($args) >= 2) {
            $first = array_shift($args);
            foreach ($args as $arg) {
                $this->assertNotSame($first, $arg);
            }
        }
    }
}
