<?php

declare(strict_types=1);

namespace Firehed\Container;

use Composer\ClassMapGenerator\ClassMapGenerator;
use RuntimeException;

/**
 * Discovers autowire-eligible classes and generates container configuration.
 */
class ConfigGenerator
{
    private ClassMapGenerator $scanner;

    /** @var list<class-string> */
    private array $excludedClasses = [];

    public function __construct()
    {
        if (!class_exists(ClassMapGenerator::class)) {
            throw new RuntimeException(
                'The config generator requires composer/class-map-generator. ' .
                'Install it with: composer require composer/class-map-generator'
            );
        }
        $this->scanner = new ClassMapGenerator();
    }

    /**
     * Add a directory to scan for classes.
     */
    public function addDirectory(string $directory): self
    {
        $this->scanner->scanPaths($directory);
        return $this;
    }

    /**
     * Exclude classes that are already defined in an existing config file.
     *
     * @param string $configFile Path to a PHP file that returns an array
     */
    public function excludeFromFile(string $configFile): self
    {
        $definitions = require $configFile;
        if (!is_array($definitions)) {
            throw new \InvalidArgumentException(sprintf(
                'Config file "%s" did not return an array',
                $configFile
            ));
        }

        foreach ($definitions as $key => $value) {
            // Handle both `ClassName::class,` and `ClassName::class => ...`
            $className = is_int($key) ? $value : $key;
            if (is_string($className) && class_exists($className)) {
                $this->excludedClasses[] = $className;
            }
        }

        return $this;
    }

    /**
     * Exclude specific classes from generation.
     *
     * @param class-string ...$classes
     */
    public function exclude(string ...$classes): self
    {
        array_push($this->excludedClasses, ...$classes);
        return $this;
    }

    /**
     * Get the list of discovered autowire-eligible classes.
     *
     * @return list<class-string>
     */
    public function getEligibleClasses(): array
    {
        $classMap = $this->scanner->getClassMap();

        $eligible = [];
        foreach ($classMap->getMap() as $className => $path) {
            if (in_array($className, $this->excludedClasses, true)) {
                continue;
            }
            if (Autowire::isEligible($className)) {
                $eligible[] = $className;
            }
        }

        sort($eligible);
        return $eligible;
    }

    /**
     * Generate the PHP config file content.
     */
    public function generate(): string
    {
        $classes = $this->getEligibleClasses();

        $lines = ["<?php", "", "declare(strict_types=1);", "", "return ["];
        foreach ($classes as $class) {
            $lines[] = sprintf("    \\%s::class,", $class);
        }
        $lines[] = "];";
        $lines[] = "";

        return implode("\n", $lines);
    }
}
