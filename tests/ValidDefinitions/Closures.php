<?php
declare(strict_types=1);

use Firehed\Container\Fixtures;
use Psr\Log\LoggerInterface as AliasedLoggerInterface;

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

    AliasedLoggerInterface::class => function (): AliasedLoggerInterface {
        return new class implements AliasedLoggerInterface
        {
            use \Psr\Log\LoggerTrait;

            public function log($level, $message, $context = [])
            {
                // no-op
            }
        };
    },

    'somethingUsingAliasedLogger' => function ($c) {
        return $c->get(AliasedLoggerInterface::class);
    },
];
