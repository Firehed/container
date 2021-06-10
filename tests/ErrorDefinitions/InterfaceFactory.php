<?php

declare(strict_types=1);

use Firehed\Container\Fixtures;

use function Firehed\Container\factory;

return [
    // Interface to factory cannot be resolved
    SessionHandlerInterface::class => factory(),
];
