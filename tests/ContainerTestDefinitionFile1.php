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

    // Factory w/ body
    DateTime::class => factory(function () {
        return new DateTime();
    }),

    // Factory autowired
    Fixtures\NoConstructorFactory::class => factory(),

    // Interface to factory implementation
    DateTimeInterface::class => DateTime::class,


    // Simple closure definitions
    Fixtures\ExplicitDefinitionInterface::class => function () {
        return new Fixtures\ExplicitDefinition();
    },

    DateTimeImmutable::class => function () {
        return new DateTimeImmutable();
    },

    // Literals
    'string_literal' => 'UnitTest',
    'int_literal' => 42,
    'float_literal' => 123.45,
    'bool_literal' => true,
    'array_literal' => ['a', 'b', 'c'],
    'dict_literal' => ['a' => 1, 'b' => 2, 'c' => 3],
];
