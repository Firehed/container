<?php
declare(strict_types=1);

use function Firehed\Container\autowire;
use Firehed\Container\Fixtures;

return [
    Fixtures\SessionHandler::class => autowire(),
    SessionHandlerInterface::class => Fixtures\SessionHandler::class,
];
