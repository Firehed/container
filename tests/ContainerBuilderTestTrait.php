<?php
declare(strict_types=1);

namespace Firehed\Container;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SessionHandlerInterface;
use SessionIdInterface;

/**
 * This is a test trait to help ensure all processes end up with the same
 * results. These are primarily integration tests, not unit tests.
 */
trait ContainerBuilderTestTrait
{
    /** @var ContainerInterface */
    private $container;

    private $rawGetEnvValue;
    private $unittestEnvVar;

    public function setUp(): void
    {
        $this->rawGetEnvValue = getenv('PWD'); // see CDF3
        $this->unittestEnvVar = md5((string)random_int(0, PHP_INT_MAX));
        putenv("CONTAINER_UNITTEST_SET={$this->unittestEnvVar}");

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
            __DIR__ . '/ContainerTestDefinitionFile3.php',
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
        $this->assertTrue($this->container->has($key), 'has should be true');
        $value = $this->container->get($key);
        $this->assertSame($expectedValue, $value, 'get should return the value');
    }

    public function testHasWithMissingKeyReturnsFalse()
    {
        $this->assertFalse($this->container->has('key_that_does_not_exist'));
    }

    public function testGetWithMissingKeyThrowsNotFoundException()
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->container->get('key_that_does_not_exist');
    }

    // Environment variables

    /**
     * This test demonstrates a counterexample
     */
    public function testRawGetenv()
    {
        $key = 'env_pwd';
        $this->assertTrue($this->container->has($key), 'has should be true');
        $value = $this->container->get($key);
        $this->assertSame($this->rawGetEnvValue, $value, 'get should return the value');
    }

    /**
     * @dataProvider envVarsThatAreSet
     */
    public function testWrappedEnv(string $key)
    {
        $this->assertTrue($this->container->has($key), 'has should be true');
        $value = $this->container->get($key);
        $this->assertSame($this->unittestEnvVar, $value, 'get should return the value');
    }

    public function testNotSetEnvVarThrowsOnAccess()
    {
        $this->assertTrue($this->container->has('env_not_set'));
        $this->expectException(ContainerExceptionInterface::class);
        $this->container->get('env_not_set');
    }

    public function testNotSetEnvVarWithDefaultReturnsDefault()
    {
        $this->assertTrue($this->container->has('env_not_set_with_default'));
        $this->assertSame('default', $this->container->get('env_not_set_with_default'));
    }

    public function testNotSetEnvVarWithNullDefaultReturnsNull()
    {
        $this->assertTrue($this->container->has('env_not_set_with_null_default'));
        $this->assertNull($this->container->get('env_not_set_with_null_default'));
    }

    // Data Providers

    public function scalarLiterals(): array
    {
        return [
            ['string_literal', 'UnitTest'],
            ['int_literal', 42],
            ['float_literal', 123.45],
            ['bool_literal', true],
            ['array_literal', ['a', 'b', 'c']],
            ['dict_literal', ['a' => 1, 'b' => 2, 'c' => 3]],
            // Sanity check for "empty" values
            ['false_literal', false],
            ['null_literal', null],
            ['zero_litreal', 0],
            ['zero_float_literal', 0.0],
            ['empty_string_literal', ''],

        ];
    }

    public function envVarsThatAreSet(): array
    {
        return [
            ['env_set'],
            ['env_set_with_default'],
            ['env_set_with_null_default'],
        ];
    }

    // Internal assertion wrappers

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
