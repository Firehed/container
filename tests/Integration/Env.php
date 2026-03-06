<?php

declare(strict_types=1);

namespace Firehed\Container\Integration;

enum Env: string
{
    case Dev = 'dev';
    case Other = 'compiled';
}
