<?php
declare(strict_types=1);

use Firehed\Container\Fixtures;

use function Firehed\Container\factory;

return [
    // Factory w/ body
    DateTime::class => factory(function (): DateTime {
        return new DateTime();
    }),

    // Factory autowired
    Fixtures\NoConstructorFactory::class => factory(),

    // Interface to factory implementation
    DateTimeInterface::class => DateTime::class,
];
