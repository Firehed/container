<?php

declare(strict_types=1);

namespace Firehed\Container\Integration;

enum VariablesOrder: string
{
    case EnvOnly = 'EGP';
    case EnvAndServer = 'EGPS';
    case ServerOnly = 'GPS';
    case Neither = 'GP';
}
