<?php
declare(strict_types=1);

use Firehed\Container\{
    Fixtures,
    Fixtures\EmptyInterface as EI,
    TypedContainerInterface,
};

return [
    // Simple closure definitions
    Fixtures\ExplicitDefinitionInterface::class => function (): Fixtures\ExplicitDefinitionInterface {
        return new Fixtures\ExplicitDefinition();
    },

    DateTimeImmutable::class => function (): DateTimeImmutable {
        return new DateTimeImmutable();
    },

    'literalValueForComplex' => 42,

    'complexDefinition' => function (TypedContainerInterface $container) {
        return $container->get('literalValueForComplex');
    },

    EI::class => function (): EI {
        return new class implements EI
        {
        };
    },

    'somethingUsingAliasedName' => function (TypedContainerInterface $c) {
        return $c->get(EI::class);
    },

    Fixtures\VariadicParam::class => fn (TypedContainerInterface $c) => new Fixtures\VariadicParam(
        $c->get(SessionIdInterface::class),
        $c->get(SessionIdInterface::class),
    ),

    'variadicWithNoArgs' => fn () => new Fixtures\VariadicParam(),

    Fixtures\UntypedVariadicParam::class => fn () => new Fixtures\UntypedVariadicParam('a', 'b'),

    'somethingWithMatch' => fn (TypedContainerInterface $c) => match ($c->get('string_literal')) {
        'UnitTest' => 'foobar',
        'other' => 'bar',
        default => 'baz',
    },
];
