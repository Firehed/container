<?php
declare(strict_types=1);

namespace Firehed\Container;

use Closure;

class Factory implements FactoryInterface
{
    /** @var ?Closure */
    private $def;

    public function __construct(?Closure $def)
    {
        $this->def = $def;
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
