<?php
declare(strict_types=1);

use Firehed\Container\Fixtures;

return [
    SessionIdInterface::class => Fixtures\SessionId::class,
    Fixtures\SessionId::class,
    Fixtures\VariadicParam::class,
];
