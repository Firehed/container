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
 * @internal
 */
class FileSource implements ValueSourceInterface
{
    public function __construct(private string $path)
    {
    }

    public function generateReadExpression(): string
    {
        return sprintf('(new \\%s(%s))->read($this->envReader)', self::class, var_export($this->path, true));
    }

    public function getName(): string
    {
        return $this->path;
    }

    /**
     * Trailing line breaks are stripped since secret files are commonly
     * written with a terminating newline that is not part of the value.
     */
    public function read(EnvReader $envReader): string
    {
        if (!is_file($this->path) || !is_readable($this->path)) {
            throw new Exceptions\FileNotReadable($this->path);
        }
        $contents = file_get_contents($this->path);
        if ($contents === false) {
            throw new Exceptions\FileNotReadable($this->path);
        }
        return rtrim($contents, "\r\n");
    }
}
