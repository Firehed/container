<?php

declare(strict_types=1);

namespace Firehed\Container\Fixtures;

use SessionHandlerInterface;
use SessionIdInterface;

class MixedParams
{
    public function __construct(
        public readonly SessionIdInterface $id,
        public readonly int $count = 3,
        public readonly ?SessionHandlerInterface $handler = null,
    ) {
    }
}
