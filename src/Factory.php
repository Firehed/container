<?php
declare(strict_types=1);

namespace Firehed\Container;

use Closure;

class Factory implements FactoryInterface
{
    public function __construct(private ?Closure $def)
    {
    }

    public function hasDefinition(): bool
    {
        return $this->def !== null;
    }

    public function getDefinition(): Closure
    {
        assert($this->def !== null);
        return $this->def;
    }
}
