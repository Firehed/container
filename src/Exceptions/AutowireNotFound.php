<?php

declare(strict_types=1);

namespace Firehed\Container\Exceptions;

class AutowireNotFound extends NotFound
{
    public function __construct(
        // container index, typically FQCN
        string $id,
        // variable name in class constructor
        string $paramName,
        string $classBeingAutowired
    ) {
        $message = sprintf(
            'Cannot autowire class %s: container does not have %s (constructor paramater $%s)',
            $classBeingAutowired,
            $id,
            $paramName
        );
        parent::__construct($message);
    }
}
