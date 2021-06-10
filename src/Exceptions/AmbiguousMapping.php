<?php
declare(strict_types=1);

namespace Firehed\Container\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class AmbiguousMapping extends Exception implements ContainerExceptionInterface
{
    public function __construct(string $key)
    {
        parent::__construct(sprintf(
            'Cannot determine target for key `%s`',
            $key
        ));
    }
}
