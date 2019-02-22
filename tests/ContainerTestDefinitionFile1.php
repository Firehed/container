<?php
declare(strict_types=1);

// use function Firehed\Container\factory;
use Firehed\Container\Fixtures;

return [
    // Simple autowiring
    Fixtures\SessionId::class,
    Fixtures\SessionHandler::class,

    // Interface to implementations
    SessionIdInterface::class => Fixtures\SessionId::class,
    SessionHandlerInterface::class => Fixtures\SessionHandler::class,

    // Factory
    // factory(DateTime::class),

    // Interface to factory implementation
    DateTimeInterface::class => DateTime::class,

    // Redundant mapping
    // Firehed\SimpleLogger\Stderr::class => Firehed\SimpleLogger\Stderr::class,
];
