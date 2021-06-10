<?php

declare(strict_types=1);

use Firehed\Container\Fixtures;

return [
    // It's assumed that any array key that's an interface name which doesn't
    // map to a factory or closure is an error.
    SessionHandlerInterface::class => 'Not a class',
];
