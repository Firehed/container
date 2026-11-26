<?php

declare(strict_types=1);

namespace Firehed\Container\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class NonPublicConstructor extends Exception implements ContainerExceptionInterface
{
    public function __construct(string $className)
    {
        parent::__construct(sprintf(
            'Cannot autowire class `%s` because its constructor is not public; define it explicitly instead',
            $className,
        ));
    }
}
