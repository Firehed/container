<?php
declare(strict_types=1);

namespace Firehed\Container\Fixtures;

use SessionIdInterface;

class SessionId implements SessionIdInterface
{
    public function create_sid()
    {
    }
}
