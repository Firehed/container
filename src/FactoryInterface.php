<?php
declare(strict_types=1);

namespace Firehed\Container;

use Closure;

interface FactoryInterface
{
    public function getDefinition(): Closure;
    public function hasDefinition(): bool;
}
