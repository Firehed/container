<?php
declare(strict_types=1);

namespace Firehed\Container\Fixtures;

class OptionalScalarParam
{
    public function __construct(private ?string $param = null)
    {
    }

    public function getParam(): ?string
    {
        return $this->param;
    }
}
