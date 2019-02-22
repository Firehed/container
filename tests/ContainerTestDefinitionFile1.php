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
    DateTime::class => factory(function () {
        return new DateTime();
    }),

    Fixtures\NoConstructorFactory::class => factory(),

    DateTimeImmutable::class => function () {
        return new DateTimeImmutable();
    },

    // Interface to factory implementation
    DateTimeInterface::class => DateTime::class,


    Fixtures\ExplicitDefinitionInterface::class => function () {
        return new Fixtures\ExplicitDefinition();
    },
];
