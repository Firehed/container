<?php

declare(strict_types=1);

namespace Firehed\Container\Fixtures;

use Closure;

class RequiresClosure
{
    public function __construct(private Closure $callback)
    {
    }
}
