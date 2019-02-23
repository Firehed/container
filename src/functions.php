<?php

namespace Firehed\Container;

use Closure;

function autowire(): AutowireInterface
{
    return new class implements AutowireInterface
    {
    };
}

function factory(?Closure $def = null): FactoryInterface
{
    return new Factory($def);
}
