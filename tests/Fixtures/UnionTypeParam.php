<?php

declare(strict_types=1);

namespace Firehed\Container\Fixtures;

class UnionTypeParam
{
    public function __construct(private SessionId|SessionIdManual $id)
    {
    }
}
