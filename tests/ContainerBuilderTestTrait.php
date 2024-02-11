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

use function version_compare;

/**
 * This is a test trait to help ensure all processes end up with the same
 * results. These are primarily integration tests, not unit tests.
 */
trait ContainerBuilderTestTrait
{
    use EnvironmentDefinitionsTestTrait;
    use ErrorDefinitionsTestTrait;

    public function getContainer(): ContainerInterface
    {
        $builder = $this->getBuilder();
        foreach (self::getDefinitionFiles() as $file) {
            $builder->addFile($file);
        }
        return $builder->build();
    }

    abstract protected function getBuilder(): BuilderInterface;

    /** @return string[] */
    private static function getDefinitionFiles(): array
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
        if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
            $files[] = 'ShortClosures';
        }
        if (version_compare(PHP_VERSION, '8.1.0-dev', '>=')) {
            $files[] = 'Enums';
        }
        return array_map(function ($name): string {
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
     * SomeName => function ($container) {
     *   return useContainerValue($container);
     * }
     */
    public function testClosureThatUsesConatiner(): void
    {
        $container = $this->getContainer();
        assert($container->has('literalValueForComplex'));
        $expected = $container->get('literalValueForComplex');
        $actual = $container->get('complexDefinition');
        $this->assertSame(
            $expected,
            $actual,
            'Complex definition which consumed container evaluated incorrectly'
        );
    }

    public function testShortClosureThatUsesContainer(): void
    {
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            self::markTestSkipped('Short closures only testable in 7.4 or later');
        }
        $container = $this->getContainer();
        assert($container->has('valueForShortClosure'));
        $expected = $container->get('valueForShortClosure');
        $actual = $container->get('shortClosure');
        $this->assertSame(
            $expected,
            $actual,
            'Complex definition which consumed container evaluated incorrectly'
        );
    }

    public function testDynamicEnum(): void
    {
        if (version_compare(PHP_VERSION, '8.1.0-dev', '<')) {
            self::markTestSkipped('Enums only testable in 8.1 or later');
        }
        $container = $this->getContainer();
        assert($container->has(Fixtures\Environment::class));
        $expected = Fixtures\Environment::TESTING;
        $actual = $container->get(Fixtures\Environment::class);
        $this->assertSame($expected, $actual, 'Dynamic enum value mismatched');
    }

    public function testHardcodedEnum(): void
    {
        if (version_compare(PHP_VERSION, '8.1.0-dev', '<')) {
            self::markTestSkipped('Enums only testable in 8.1 or later');
        }
        $container = $this->getContainer();
        assert($container->has('enum_hardcoded'));
        $expected = Fixtures\Environment::STAGING;
        $actual = $container->get('enum_hardcoded');
        $this->assertSame($expected, $actual, 'Hardcoded enum value mismatched');
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
        assert($dsp instanceof Fixtures\DefaultScalarParam);
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
        assert($osp instanceof Fixtures\OptionalScalarParam);
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

    /**
     * @covers \Firehed\Container\Compiler\ClosureValue
     * @covers \Firehed\Container\Compiler\ClosureVisitor
     */
    public function testAliasedImportsAreNotMangled(): void
    {
        $container = $this->getContainer();
        $i1 = $container->get(Fixtures\EmptyInterface::class);
        $i2 = $container->get('somethingUsingAliasedName');
        $this->assertSame($i1, $i2);
        $this->assertInstanceOf(Fixtures\EmptyInterface::class, $i1);
    }

    // Data Providers

    /** @return mixed[][] */
    public static function scalarLiterals(): array
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
     * @param ?class-string $type
     * @return mixed The fetched value
     */
    private function assertGetSingleton(ContainerInterface $container, string $key, ?string $type = null)
    {
        /** @var class-string<object> $type */
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

    /**
     * @param ?class-string $type
     */
    private function assertGetFactory(ContainerInterface $container, string $key, ?string $type = null): void
    {
        /** @var class-string $type */
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
