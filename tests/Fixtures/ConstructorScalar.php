<?php
declare(strict_types=1);

namespace Firehed\Container\Fixtures;

class ConstructorScalar
{
    public function __construct(private string $string)
    {
    }
}
