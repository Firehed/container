<?php

declare(strict_types=1);

use Firehed\Container\Fixtures\CustomDefinition;

return [
    'custom_string' => new CustomDefinition('hello from custom'),
    'custom_int' => new CustomDefinition(42),
    'custom_array' => new CustomDefinition(['a', 'b', 'c']),
    'custom_cacheable' => new CustomDefinition('cached', cacheable: true),
    'custom_factory' => new CustomDefinition('not cached', cacheable: false),
];
