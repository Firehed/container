<?php
declare(strict_types=1);

use function Firehed\Container\autowire;
use Firehed\Container\Fixtures;

return [
    // Simple autowiring
    Fixtures\SessionId::class => autowire(),

    // Interface to implementations
    SessionIdInterface::class => Fixtures\SessionId::class,
];
