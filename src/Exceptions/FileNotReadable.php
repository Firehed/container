<?php

declare(strict_types=1);

namespace Firehed\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class FileNotReadable extends RuntimeException implements ContainerExceptionInterface
{
    public function __construct(string $path)
    {
        parent::__construct("File not readable: '$path'");
    }
}
