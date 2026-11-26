<?php

declare(strict_types=1);

namespace Firehed\Container\Fixtures;

class CountsArguments
{
    public readonly int $count;

    public function __construct(int $value = 5)
    {
        $this->count = func_num_args();
    }
}
