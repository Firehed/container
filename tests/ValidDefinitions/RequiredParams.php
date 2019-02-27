<?php
declare(strict_types=1);

use function Firehed\Container\autowire;
use Firehed\Container\Fixtures;

return [
    // Autowire a class with a required constructor param
    Fixtures\SessionHandler::class => autowire(),

    // Autowire an interface to an implementation of a class with a required
    // constructor param
    SessionHandlerInterface::class => Fixtures\SessionHandler::class,
];
