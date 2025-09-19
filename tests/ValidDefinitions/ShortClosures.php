<?php
declare(strict_types=1);

use Firehed\Container\{
    Fixtures,
    TypedContainerInterface,
};

return [
    'valueForShortClosure' => 42,

    'shortClosure' => fn (TypedContainerInterface $c) => $c->get('literalValueForComplex'),
];
