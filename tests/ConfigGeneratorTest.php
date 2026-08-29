<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\TestCase;

/**
 * @covers Firehed\Container\ConfigGenerator
 */
class ConfigGeneratorTest extends TestCase
{
    public function testScanFindsEligibleClasses(): void
    {
        $generator = new ConfigGenerator();
        $generator->addDirectory(__DIR__ . '/Fixtures');

        $classes = $generator->getEligibleClasses();

        // These should be found (autowire-eligible)
        self::assertContains(Fixtures\SessionId::class, $classes);
        self::assertContains(Fixtures\SessionHandler::class, $classes);
        self::assertContains(Fixtures\OptionalScalarParam::class, $classes);
        self::assertContains(Fixtures\NoConstructorFactory::class, $classes);

        // These should NOT be found
        self::assertNotContains(Fixtures\ConstructorScalar::class, $classes);
        self::assertNotContains(Fixtures\ConstructorUntyped::class, $classes);
        self::assertNotContains(Fixtures\EmptyInterface::class, $classes);
        self::assertNotContains(Fixtures\Environment::class, $classes);
        // Closure cannot be autowired
        self::assertNotContains(Fixtures\RequiresClosure::class, $classes);
    }

    public function testExcludeRemovesClasses(): void
    {
        $generator = new ConfigGenerator();
        $generator->addDirectory(__DIR__ . '/Fixtures');
        $generator->exclude(Fixtures\SessionId::class);

        $classes = $generator->getEligibleClasses();

        self::assertNotContains(Fixtures\SessionId::class, $classes);
        self::assertContains(Fixtures\SessionHandler::class, $classes);
    }

    public function testExcludeFromFileRemovesDefinedClasses(): void
    {
        $generator = new ConfigGenerator();
        $generator->addDirectory(__DIR__ . '/Fixtures');
        $generator->excludeFromFile(__DIR__ . '/ValidDefinitions/NoParams.php');

        $classes = $generator->getEligibleClasses();

        // These are defined as keys in NoParams.php
        self::assertNotContains(Fixtures\SessionId::class, $classes);
        self::assertNotContains(Fixtures\SessionIdImplicit::class, $classes);
        // SessionIdManual is a value (autowire target), not a key, so still included
        self::assertContains(Fixtures\SessionIdManual::class, $classes);
    }

    public function testGenerateProducesValidPhp(): void
    {
        $generator = new ConfigGenerator();
        $generator->addDirectory(__DIR__ . '/Fixtures');

        $output = $generator->generate();

        self::assertStringStartsWith("<?php\n", $output);
        self::assertStringContainsString('declare(strict_types=1);', $output);
        self::assertStringContainsString('return [', $output);
        self::assertStringContainsString(Fixtures\SessionId::class . '::class,', $output);
    }

    public function testAddDirectoryThrowsForNonexistentDirectory(): void
    {
        $generator = new ConfigGenerator();

        $this->expectException(\RuntimeException::class);
        $generator->addDirectory('/nonexistent/path');
    }
}
