<?php

declare(strict_types=1);

use Firehed\Container\Fixtures;

use function Firehed\Container\factory;

return [
    // Non-class to factory (without body) cannot be resolved
    'hello' => factory(),
];
