<?php

declare(strict_types=1);

namespace Firehed\Container\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class IncorrectlyTypedValue extends Exception implements ContainerExceptionInterface
{
    public function __construct(
        string $id,
        string $expectedType,
        string $actualType,
    ) {
        parent::__construct(sprintf(
            'Could not retrieve `%s` as %s, value is %s',
            $id,
            $expectedType,
            $actualType,
        ));
    }
}
