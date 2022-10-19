<?php
declare(strict_types=1);

namespace Firehed\Container\Exceptions;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class NotFound extends Exception implements NotFoundExceptionInterface
{
    public static function autowireMissing(
        string $type,
        string $classBeingAutowired,
        string $paramName = ''
    ): NotFound {
        $message = sprintf(
            'Cannot autowire class %s: container does not have index "%s"',
            $classBeingAutowired,
            $type
        );
        if ($paramName !== '') {
            $message .= sprintf(' (constructor parameter $%s)', $paramName);
        }
        return new NotFound($message);
    }
}
