<?php
declare(strict_types=1);

namespace Firehed\Container\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class InvalidClassMapping extends Exception implements ContainerExceptionInterface
{
    public function __construct(string $interfaceName, string $nonClassName)
    {
        parent::__construct(sprintf(
            'Interface `%s` cannot be mapped to non-class `%s`',
            $interfaceName,
            $nonClassName
        ));
    }
}
