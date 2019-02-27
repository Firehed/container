<?php
declare(strict_types=1);

namespace Firehed\Container\Fixtures;

class ConstructorScalar
{
    /** @var string */
    private $string;
    public function __construct(string $string)
    {
        $this->string = $string;
    }
}
