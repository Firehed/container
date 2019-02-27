<?php
declare(strict_types=1);

namespace Firehed\Container\Fixtures;

class OptionalScalarParam
{
    /** @var ?string */
    private $param;

    public function __construct(string $param = null)
    {
        $this->param = $param;
    }

    public function getParam(): ?string
    {
        return $this->param;
    }
}
