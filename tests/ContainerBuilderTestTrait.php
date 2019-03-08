<?php
declare(strict_types=1);

namespace Firehed\Container;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SessionHandlerInterface;
use SessionIdInterface;

/**
 * This is a test trait to help ensure all processes end up with the same
 * results. These are primarily integration tests, not unit tests.
 */
trait ContainerBuilderTestTrait
{
    use EnvironmentDefinitionsTestTrait;
    use ErrorDefinitionsTestTrait;

    /** @var string */
    private $rawGetEnvValue;

    public function getContainer(): ContainerInterface
    {
        $this->rawGetEnvValue = (string)getenv('PWD'); // see CDF3

        $builder = $this->getBuilder();
        foreach ($this->getDefinitionFiles() as $file) {
            $builder->addFile($file);
        }
        return $builder->build();
    }

    abstract protected function getBuilder(): BuilderInterface;

    private function getDefinitionFiles(): array
    {
        $files = [
            'Environment',
            'CaseSensitive',
            'RequiredParams',
            'Factories',
            'Literals',
            'Closures',
            'NoParams',
            'ScalarParams',
        ];
        return array_map(function ($name) {
            return sprintf('%s/ValidDefinitions/%s.php', __DIR__, $name);
        }, $files);
    }

    /**
     * SomeImplementation::class => autowire()
     * where SomeImplementation has no constructor arguments
     */
    public function testAutowiredDefinition(): void
    {
        $container = $this->getContainer();
        $this->assertGetSingleton(
            $container,
            Fixtures\SessionId::class
        );
    }

    /**
     * SomeImplementation::class => autowire(SomeImplementation::class)
     * where SomeImplementation has no constructor arguments
     */
    public function testAutowiredExplicitDefinition(): void
    {
        $container = $this->getContainer();
        $this->assertGetSingleton(
            $container,
            'AliasOfSessionIdManual',
            Fixtures\SessionIdManual::class
        );
    }

    /**
     * SomeImplementation::class,
     */
    public function testImplcitAutowiredDefinition(): void
    {
        $container = $this->getContainer();
        $this->assertGetSingleton(
            $container,
            Fixtures\SessionIdImplicit::class
        );
    }

    /**
     * SomeInterface::class => SomeImplementation::class
     * SomeImplementation::class => autowire()
     * where SomeImplementation has no constructor arguments
     */
    public function testInterfaceMapping(): void
    {
        $container = $this->getContainer();
        $this->assertGetSingleton(
            $container,
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
        $container = $this->getContainer();
        $this->assertGetSingleton(
            $container,
            Fixtures\SessionHandler::class
        );
    }

    /**
     * SomeInterface::class => SomeImplementation::class
     * SomeImplementation::class => autowire()
     * where SomeImplementation has >= 1 constructor arguments
     */
    public function testInterfaceMappedToAutowiredDefinitionWithConstructorArg(): void
    {
        $container = $this->getContainer();
        $this->assertGetSingleton(
            $container,
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
        $container = $this->getContainer();
        $this->assertGetFactory(
            $container,
            DateTime::class
        );
    }

    /**
     * SomeInterface::class => SomeImplementation::clas;
     * SomeImplementation::class => factory(...)
     */
    public function testMultipleCallsToInterfaceMappedToFactoryDefinitionWithBody(): void
    {
        $container = $this->getContainer();
        $this->assertGetFactory(
            $container,
            DateTimeInterface::class,
            DateTime::class
        );
    }

    /**
     * SomeImplementation::class => factory()
     */
    public function testMultipleCallsToFactoryWithNoBodyReturnDifferentObjects(): void
    {
        $container = $this->getContainer();
        $this->assertGetFactory(
            $container,
            Fixtures\NoConstructorFactory::class
        );
    }

    /**
     * SomeInterface::class => function () {
     *   return new SomeImplementation();
     * }
     */
    public function testInterfaceKeyToExplicitDefinition(): void
    {
        $container = $this->getContainer();
        $this->assertGetSingleton(
            $container,
            Fixtures\ExplicitDefinitionInterface::class
        );
    }

    /**
     * some_string => scalar_literal
     * @dataProvider scalarLiterals
     * @param mixed $expectedValue
     */
    public function testScalarLiteral(string $key, $expectedValue): void
    {
        $container = $this->getContainer();
        $this->assertTrue($container->has($key), 'has should be true');
        $value = $container->get($key);
        $this->assertSame($expectedValue, $value, 'get should return the value');
    }

    /**
     * DefaultScalarParam::class => autowire()
     */
    public function testDefaultScalarParamCanBeAutowired(): void
    {
        $container = $this->getContainer();
        $dsp = $this->assertGetSingleton(
            $container,
            Fixtures\DefaultScalarParam::class
        );
        // Should have autowired with default from signature
        $this->assertSame(
            Fixtures\DefaultScalarParam::DEFAULT_VALUE,
            $dsp->getParam()
        );
    }

    /**
     * OptionalScalarParam::class => autowire()
     */
    public function testOptionalScalarParamCanBeAutowired(): void
    {
        $container = $this->getContainer();
        $osp = $this->assertGetSingleton(
            $container,
            Fixtures\OptionalScalarParam::class
        );
        // Should have autowired with null default from signature
        $this->assertNull($osp->getParam());
    }

    public function testHasWithMissingKeyReturnsFalse(): void
    {
        $container = $this->getContainer();
        $this->assertFalse($container->has('key_that_does_not_exist'));
    }

    public function testGetWithMissingKeyThrowsNotFoundException(): void
    {
        $container = $this->getContainer();
        $this->expectException(NotFoundExceptionInterface::class);
        $container->get('key_that_does_not_exist');
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
            // Case-sensitive keys
            ['case_sensitive', 1],
            ['CASE_SENSITIVE', 2],
            ['Case_Sensitive', 3],
            ['CaSe_SeNsItIvE', 4],
        ];
    }

    // Internal assertion wrappers

    /**
     * @return mixed The fetched value
     */
    private function assertGetSingleton(ContainerInterface $container, string $key, ?string $type = null)
    {
        $type = $type ?? $key;
        $this->assertTrue($container->has($key));
        $values = [];
        for ($i = 0; $i < 3; $i++) {
            $value = $container->get($key);
            $this->assertInstanceOf($type, $value);
            $values[] = $value;
        }
        $this->assertAllAreSame($values);
        return $values[0];
    }

    private function assertGetFactory(ContainerInterface $container, string $key, ?string $type = null): void
    {
        $type = $type ?? $key;
        $this->assertTrue($container->has($key), "Container should have $key");
        $values = [];
        for ($i = 0; $i < 3; $i++) {
            $value = $container->get($key);
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
