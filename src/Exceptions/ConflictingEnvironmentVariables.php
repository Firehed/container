<?php

declare(strict_types=1);

namespace Firehed\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class ConflictingEnvironmentVariables extends RuntimeException implements ContainerExceptionInterface
{
    public function __construct(string $first, string $second)
    {
        parent::__construct("Only one of $first and $second may be set");
    }
}
