<?php

declare(strict_types=1);

use Firehed\Container\Fixtures;

use function Firehed\Container\autowire;

return [
    // Explicit interface mapping is ambiguous
    SessionHandlerInterface::class => autowire(),
];
