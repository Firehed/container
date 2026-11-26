<?php

declare(strict_types=1);

namespace Firehed\Container\Fixtures;

use SessionIdInterface;

class VariadicParam
{
    /** @var SessionIdInterface[] */
    public readonly array $ids;

    public function __construct(SessionIdInterface ...$ids)
    {
        $this->ids = $ids;
    }
}
