<?php
declare(strict_types=1);

use Firehed\Container\Fixtures;
use Firehed\Container\Fixtures\EmptyInterface as EI;

return [
    // Simple closure definitions
    Fixtures\ExplicitDefinitionInterface::class => function (): Fixtures\ExplicitDefinitionInterface {
        return new Fixtures\ExplicitDefinition();
    },

    DateTimeImmutable::class => function (): DateTimeImmutable {
        return new DateTimeImmutable();
    },

    'literalValueForComplex' => 42,

    'complexDefinition' => function ($container) {
        return $container->get('literalValueForComplex');
    },

    EI::class => function (): EI {
        return new class implements EI
        {
        };
    },

    'somethingUsingAliasedName' => function ($c) {
        return $c->get(EI::class);
    },
];
