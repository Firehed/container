<?php
declare(strict_types=1);

use Firehed\Container\Fixtures;

return [
    'valueForShortClosure' => 42,

    'shortClosure' => fn ($c) => $c->get('literalValueForComplex'),
];
