<?php

declare(strict_types=1);

namespace Firehed\Container\Fixtures;

class UntypedVariadicParam
{
    /** @var mixed[] */
    public readonly array $ids;

    public function __construct(mixed ...$ids)
    {
        $this->ids = $ids;
    }
}
