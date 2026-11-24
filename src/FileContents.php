<?php

declare(strict_types=1);

namespace Firehed\Container;

use function file_get_contents;
use function is_file;
use function is_readable;
use function rtrim;
use function sprintf;
use function var_export;

/**
 * @internal - use `Firehed\Container\fileContents()` to reach this.
 */
class FileContents implements CastableValueInterface, DefinitionInterface
{
    use CastableValueTrait;

    public function __construct(private readonly string $path)
    {
    }

    public function generateCode(): string
    {
        return sprintf(
            'return (new \\%s(%s))->resolve($this, $this->envReader);',
            self::class,
            var_export($this->path, true),
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

    public function resolve(TypedContainerInterface $container, EnvReader $envReader): string
    {
        if (!is_file($this->path) || !is_readable($this->path)) {
            throw new Exceptions\FileNotReadable($this->path);
        }
        $contents = file_get_contents($this->path);
        if ($contents === false) {
            throw new Exceptions\FileNotReadable($this->path);
        }
        // Editors and `echo` commonly leave a trailing newline that is not
        // part of the intended value.
        return rtrim($contents, "\r\n");
    }
}
