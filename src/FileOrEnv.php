<?php

declare(strict_types=1);

namespace Firehed\Container;

use function func_num_args;
use function implode;
use function sprintf;
use function var_export;

/**
 * Reads the file named by `{$name}_FILE` if that is set, otherwise reads
 * `$name` the same way as `env()`.
 *
 * @internal - use `Firehed\Container\fileOrEnv()` to reach this.
 */
class FileOrEnv implements DefinitionInterface
{
    private bool $hasDefault;

    public function __construct(private readonly string $name, private readonly ?string $default = null)
    {
        $this->hasDefault = func_num_args() === 2;
    }

    public function generateCode(): string
    {
        $args = [var_export($this->name, true)];
        if ($this->hasDefault) {
            $args[] = var_export($this->default, true);
        }
        return sprintf(
            'return (new \\%s(%s))->resolve($this, $this->envReader);',
            self::class,
            implode(', ', $args),
        );
    }

    /** @return class-string[] */
    public function getDependencies(): array
    {
        return [];
    }

    public function isCacheable(): bool
    {
        return true;
    }

    public function resolve(TypedContainerInterface $container, EnvReader $envReader): ?string
    {
        $fileVar = $this->name . '_FILE';
        $path = $envReader->read($fileVar);
        if ($path === null) {
            $env = $this->hasDefault
                ? new EnvironmentVariable($this->name, $this->default)
                : new EnvironmentVariable($this->name);
            return $env->resolve($container, $envReader);
        }
        if ($envReader->read($this->name) !== null) {
            throw new Exceptions\ConflictingEnvironmentVariables($this->name, $fileVar);
        }
        return (new FileContents($path))->resolve($container, $envReader);
    }
}
