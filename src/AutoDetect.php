<?php

declare(strict_types=1);

namespace Firehed\Container;

use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

final class AutoDetect
{
    public const ENVIRONMENT_NAMES = [
        'ENVIRONMENT',
        'ENV',
    ];

    public static string $compiledOutputPath = 'vendor/compiledConfig.php';

    private static ?TypedContainerInterface $instance = null;

    private static string $instanceDirectory;

    private function __construct()
    {
    }

    /**
     * Returns a Builder or Compiler based on environment detection.
     *
     * In development environments (dev, development, local), returns a Builder.
     * In other environments, returns a Compiler that writes to the specified
     * output path (or the default $compiledOutputPath).
     *
     * @param non-empty-array<literal-string> $envNames
     */
    public static function getBuilder(
        array $envNames = self::ENVIRONMENT_NAMES,
        ?string $compiledOutputPath = null,
    ): BuilderInterface {
        $reader = new EnvReader($_ENV);
        $env = null;
        foreach ($envNames as $envName) {
            $env = $reader->read($envName);
            if ($env !== null && $env !== '') {
                break;
            }
        }

        if (!is_string($env) || $env === '') {
            throw new UnexpectedValueException(sprintf(
                'Could not detect environment name. Searched envvars: %s',
                implode(', ', $envNames),
            ));
        }

        $env = strtolower($env);
        if ($env === 'dev' || $env === 'development' || $env === 'local') {
            return new Builder();
        }

        return new Compiler($compiledOutputPath ?? self::$compiledOutputPath);
    }

    /**
     * Imports all definitions in the directory provided, and builds into
     * a container. This path is relative to your current working directory.
     *
     * This will look for common environment naming conventions and use either
     * the dynamic or compiled config builder based on whether a development
     * environment is detected.
     *
     * @param non-empty-literal-string $directory
     * @param non-empty-array<literal-string> $envNames
     */
    public static function from(string $directory, array $envNames = self::ENVIRONMENT_NAMES): TypedContainerInterface
    {
        $builder = self::getBuilder($envNames);
        $builder->addDirectory($directory);
        return $builder->build();
    }

    /**
     * Singleton wrapper for ::from($directory). While you should use the
     * container to manage object instances, it's possible to run into subtle
     * issues if there are multiple instances of the container itself
     *
     * @param non-empty-literal-string $directory
     * @param non-empty-array<literal-string> $envNames
     */
    public static function instance(
        string $directory,
        array $envNames = self::ENVIRONMENT_NAMES,
    ): TypedContainerInterface {
        if (self::$instance === null) {
            self::$instance = self::from($directory, $envNames);
            self::$instanceDirectory = $directory;
        } elseif ($directory !== self::$instanceDirectory) {
            // You're gonna have a bad time.
            throw new InvalidArgumentException('Instance must receive the same directory each time');
        }
        return self::$instance;
    }
}
