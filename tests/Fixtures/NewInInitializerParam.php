<?php

declare(strict_types=1);

namespace Firehed\Container\Fixtures;

use SessionIdInterface;

class NewInInitializerParam
{
    public function __construct(
        public readonly NotInContainer $dependency = new NotInContainer(),
        public readonly ?SessionIdInterface $sessionId = null,
    ) {
    }
}
