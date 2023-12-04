<?php

declare(strict_types=1);

namespace Firehed\Container;

use RuntimeException;
use UnexpectedValueException;

class AutoDetect
{
    private static ?TypedContainerInterface $instance = null;

    private function __construct()
    {
    }
    /**
     * Imports all definitions in the directory provided, and builds into
     * a container.
     *
     * This will look for common environment naming conventions and use either
     * the dynamic or compiled config builder based on whether a development
     * environment is detected.
     */
    public static function from(string $directory): TypedContainerInterface
    {
        if ($directory === '') {
            throw new UnexpectedValueException('Directory is empty. Did you mean "."?');
        }

        $env = getenv('ENVIRONMENT');
        if ($env === false) {
            $env = getenv('ENV');
        }
        if ($env === false) {
            throw new UnexpectedValueException('Could not find an environment name in ENVIRONMENT or ENV');
        }

        $env = strtolower($env);
        if ($env === 'dev' || $env === 'development' || $env === 'local') {
            $builder = new Builder();
        } else {
            $builder = new Compiler();
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
     * Singleton wrapper for ::from($directory)
     */
    public static function instance(string $directory): TypedContainerInterface
    {
        if (self::$instance === null) {
            self::$instance = self::from($directory);
        }
        return self::$instance;
    }
}
