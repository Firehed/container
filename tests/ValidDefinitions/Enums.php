<?php

declare(strict_types=1);

use Firehed\Container\Fixtures\Environment;
use Firehed\Container\TypedContainerInterface;

return [
    // Normally env('ENVIRONMENT') or something. For unit tests, the source
    // doesn't matter so it's left hardcoded for simplicity.
    'enum_env' => 'testing',

    // Dynamic value
    Environment::class => fn (TypedContainerInterface $c) => Environment::from($c->get('enum_env')),

    // Hard-coded value
    'enum_hardcoded' => Environment::STAGING,

];
