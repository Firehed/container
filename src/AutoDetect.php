<?php

declare(strict_types=1);

namespace Firehed\Container;

use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class AutoDetect
{
    public static string $compiledOutputPath = 'vendor/compiledConfig.php';

    private static ?TypedContainerInterface $instance = null;

    private static string $instanceDirectory;

    private function __construct()
    {
    }

    /**
     * Imports all definitions in the directory provided, and builds into
     * a container. This path is relative to your current working directory.
     *
     * This will look for common environment naming conventions and use either
     * the dynamic or compiled config builder based on whether a development
     * environment is detected.
     */
    public static function from(string $directory): TypedContainerInterface
    {
        if ($directory === '') {
            throw new InvalidArgumentException('Directory is empty. Did you mean "."?');
        }

        $env = getenv('ENVIRONMENT');
        if ($env === false || $env === '') {
            $env = getenv('ENV');
        }
        if ($env === false || $env === '') {
            throw new UnexpectedValueException('Could not find an environment name in ENVIRONMENT or ENV');
        }

        $env = strtolower($env);
        if ($env === 'dev' || $env === 'development' || $env === 'local') {
            $builder = new Builder();
        } else {
            $builder = new Compiler(self::$compiledOutputPath);
        }

        $files = glob($directory . '/*.php');
        if ($files === false) {
            throw new RuntimeException('Could not read config directory');
        }
        if ($files === []) {
            throw new UnexpectedValueException('No config files found in the specified directory');
        }
        foreach ($files as $file) {
            $builder->addFile($file);
        }

        return $builder->build();
    }

    /**
     * Singleton wrapper for ::from($directory). While you should use the
     * container to manage object instances, it's possible to run into subtle
     * issues if there are multiple instances of the container itself
     */
    public static function instance(string $directory): TypedContainerInterface
    {
        if (self::$instance === null) {
            self::$instance = self::from($directory);
            self::$instanceDirectory = $directory;
        } elseif ($directory !== self::$instanceDirectory) {
            // You're gonna have a bad time.
            throw new InvalidArgumentException('Instance must receive the same directory each time');
        }
        return self::$instance;
    }
}
