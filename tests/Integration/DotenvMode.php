<?php

declare(strict_types=1);

namespace Firehed\Container\Integration;

enum DotenvMode: string
{
    case None = 'none';
    case Immutable = 'immutable';
    case Mutable = 'mutable';
    case UnsafeMutable = 'unsafe_mutable';
    case UnsafeImmutable = 'unsafe_immutable';
}
