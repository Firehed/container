<?php
declare(strict_types=1);

namespace Firehed\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class EnvironmentVariableNotSet extends RuntimeException implements ContainerExceptionInterface
{
    public function __construct(string $varName)
    {
        parent::__construct("Environment variable not set: $varName");
    }
}
