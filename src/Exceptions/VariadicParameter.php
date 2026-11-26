<?php

declare(strict_types=1);

namespace Firehed\Container\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class VariadicParameter extends Exception implements ContainerExceptionInterface
{
    public function __construct(string $varName, string $className)
    {
        parent::__construct(sprintf(
            'Cannot autowire variadic constructor param `$%s` for class `%s`; define it explicitly instead',
            $varName,
            $className,
        ));
    }
}
