<?php

namespace Firehed\Container;

use Closure;

function autowire(?string $class = null): AutowireInterface
{
    return new AutowiredClass($class);
}

function env(string $name, ?string $default = null): EnvironmentVariableInterface
{
    // This is a little magic to separate a null literal from no argument
    // provided
    if (func_num_args() === 2) {
        return new EnvironmentVariable($name, $default);
    } else {
        return new EnvironmentVariable($name);
    }
}

function factory(?Closure $def = null): FactoryInterface
{
    return new Factory($def);
}
