<?php

declare(strict_types=1);

namespace Firehed\Container\Fixtures;

use SessionIdInterface;

class OptionalObjectParam
{
    public function __construct(
        private ?SessionIdInterface $sessionId = null,
    ) {
    }

    public function getSessionId(): ?SessionIdInterface
    {
        return $this->sessionId;
    }
}
