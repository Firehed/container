<?php
declare(strict_types=1);

namespace Firehed\Container\Fixtures;

class ConstructorUntyped
{
    public function __construct(private mixed $var)
    {
    }
}
