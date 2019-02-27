<?php
declare(strict_types=1);

namespace Firehed\Container\Fixtures;

class ConstructorUntyped
{
    /** @var mixed */
    private $var;

    /** @param mixed $var */
    public function __construct($var)
    {
        $this->var = $var;
    }
}
