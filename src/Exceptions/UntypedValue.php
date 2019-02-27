<?php
declare(strict_types=1);

namespace Firehed\Container\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class UntypedValue extends Exception implements ContainerExceptionInterface
{
    public function __construct(string $varName, string $className)
    {
        parent::__construct(sprintf(
            'Cannot infer type of constructor param `$%s` for class `%s`',
            $varName,
            $className
        ));
    }
}
