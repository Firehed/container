<?php

declare(strict_types=1);

namespace Firehed\Container\Integration;

enum Override: string
{
    case None = '';
    case Shell = 'FOO=shell';
}
