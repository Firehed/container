<?php
declare(strict_types=1);

use function Firehed\Container\autowire;
use function Firehed\Container\factory;
use Firehed\Container\Fixtures;

return [
    // Simple autowiring
    Fixtures\SessionId::class => autowire(),

    // Interface to implementations
    SessionIdInterface::class => Fixtures\SessionId::class,

    // Factory
    DateTime::class => factory(),

    // Interface to factory implementation
    DateTimeInterface::class => DateTime::class,

    // Redundant mapping
    // Firehed\SimpleLogger\Stderr::class => Firehed\SimpleLogger\Stderr::class,
];
