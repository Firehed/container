<?php
declare(strict_types=1);

namespace Firehed\Container\Fixtures;

use SessionIdInterface;

class SessionIdImplicit implements SessionIdInterface
{
    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function create_sid(): string
    {
    }
    // phpcs:enable
}
