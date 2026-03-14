<?php

declare(strict_types=1);

namespace Firehed\Container\Fixtures;

class PrivateConstructor
{
    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }
}
