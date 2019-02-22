<?php

namespace Firehed\Container;

function autowire()
{
    return new class implements AutowireInterface
    {
    };
}

function factory()
{
    return new class implements FactoryInterface
    {
    };
}
