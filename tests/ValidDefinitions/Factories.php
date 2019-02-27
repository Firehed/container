<?php
declare(strict_types=1);

use function Firehed\Container\factory;
use Firehed\Container\Fixtures;

return [
    // Factory w/ body
    DateTime::class => factory(function () {
        return new DateTime();
    }),

    // Factory autowired
    Fixtures\NoConstructorFactory::class => factory(),

    // Interface to factory implementation
    DateTimeInterface::class => DateTime::class,
];
