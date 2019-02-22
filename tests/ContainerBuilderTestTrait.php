<?php
declare(strict_types=1);

namespace Firehed\Container;

use DateTime;
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
        $this->assertTrue($this->container->has(Fixtures\SessionId::class));
        $sessionId = $this->container->get(Fixtures\SessionId::class);
        assert($sessionId instanceof Fixtures\SessionId);
    }

    /**
     * SomeImplementation::class => autowire()
     * where SomeImplementation has >=1 constructor arguments
     */
    public function testAutowiredDefinitionWithConstuctorArg(): void
    {
        $this->assertTrue($this->container->has(Fixtures\SessionHandler::class));
        $sh = $this->container->get(Fixtures\SessionHandler::class);
        assert($sh instanceof SessionHandlerInterface);
        assert($sh instanceof Fixtures\SessionHandler);
    }

    public function testMultipleGetCallsToSameObjectReturnInstance(): void
    {
        $this->assertTrue($this->container->has(Fixtures\SessionId::class));
        $sessionId1 = $this->container->get(Fixtures\SessionId::class);
        $sessionId2 = $this->container->get(Fixtures\SessionId::class);
        $sessionId3 = $this->container->get(Fixtures\SessionId::class);
        assert($sessionId1 instanceof Fixtures\SessionId);
        assert($sessionId2 instanceof Fixtures\SessionId);
        assert($sessionId3 instanceof Fixtures\SessionId);
        $this->assertAllAreSame($sessionId1, $sessionId2, $sessionId3);
    }

    /**
     * SomeInterface::class => SomeImplementation::class
     */
    public function testInterfaceMapping(): void
    {
        $this->assertTrue($this->container->has(SessionIdInterface::class));
        $sid = $this->container->get(SessionIdInterface::class);
        assert($sid instanceof SessionIdInterface);
        assert($sid instanceof Fixtures\SessionId);
    }

    public function testFirstCallToFactory(): void
    {
        $this->assertTrue($this->container->has(DateTime::class));
        $dt = $this->container->get(DateTime::class);
        assert($dt instanceof DateTime);
    }

    /**
     * SomeImplementation::class => factory(function ($c) {
     *   return new SomeImplementation($c->get('param'));
     * }
     */
    public function testMultipleCallsToFactoryWithBodyReturnDifferentObjects(): void
    {
        $this->assertTrue($this->container->has(DateTime::class));
        $dt1 = $this->container->get(DateTime::class);
        $dt2 = $this->container->get(DateTime::class);
        $dt3 = $this->container->get(DateTime::class);
        assert($dt1 instanceof DateTime);
        assert($dt2 instanceof DateTime);
        assert($dt3 instanceof DateTime);
        $this->assertAllAreNotSame($dt1, $dt2, $dt3);
    }

    /**
     * SomeImplementation::class => factory()
     */
    public function testMultipleCallsToFactoryWithNoBodyReturnDifferentObjects(): void
    {
        $this->assertTrue($this->container->has(Fixtures\NoConstructorFactory::class));
        $ncf1 = $this->container->get(Fixtures\NoConstructorFactory::class);
        $ncf2 = $this->container->get(Fixtures\NoConstructorFactory::class);
        $ncf3 = $this->container->get(Fixtures\NoConstructorFactory::class);
        assert($ncf1 instanceof Fixtures\NoConstructorFactory);
        assert($ncf2 instanceof Fixtures\NoConstructorFactory);
        assert($ncf3 instanceof Fixtures\NoConstructorFactory);

        $this->assertAllAreNotSame($ncf1, $ncf2, $ncf3);
    }

    /**
     * SomeInterface::class => function () {
     *   return new SomeImplementation();
     * }
     */
    public function testInterfaceKeyToExplicitDefinition(): void
    {
        $this->assertTrue($this->container->has(Fixtures\ExplicitDefinitionInterface::class));
        $edi = $this->container->get(Fixtures\ExplicitDefinitionInterface::class);
        assert($edi instanceof Fixtures\ExplicitDefinitionInterface);
    }

    /**
     * some_string => scalar_literak
     * @dataProvider scalarLiterals
     */
    public function testScalarLiteral(string $key, $expectedValue)
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

    private function assertAllAreSame(...$args)
    {
        while (count($args) >= 2) {
            $first = array_shift($args);
            foreach ($args as $arg) {
                $this->assertSame($first, $arg);
            }
        }
    }

    private function assertAllAreNotSame(...$args)
    {
        while (count($args) >= 2) {
            $first = array_shift($args);
            foreach ($args as $arg) {
                $this->assertNotSame($first, $arg);
            }
        }
    }
}
