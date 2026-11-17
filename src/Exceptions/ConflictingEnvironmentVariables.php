<?php

declare(strict_types=1);

namespace Firehed\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class ConflictingEnvironmentVariables extends RuntimeException implements ContainerExceptionInterface
{
    public function __construct(string $name, string $fileName)
    {
        parent::__construct("Only one of $name and $fileName may be set");
    }
}
