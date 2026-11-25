<?php

declare(strict_types=1);

namespace Firehed\Container\Fixtures;

class NotInContainer
{
    public static int $instances = 0;

    public function __construct()
    {
        self::$instances++;
    }
}
