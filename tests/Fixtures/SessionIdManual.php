<?php
declare(strict_types=1);

namespace Firehed\Container\Fixtures;

use SessionIdInterface;

class SessionIdManual implements SessionIdInterface
{
    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function create_sid(): string
    {
        return 'sid';
    }
    // phpcs:enable
}
