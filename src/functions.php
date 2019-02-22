<?php

namespace Firehed\Container;

use Closure;

function autowire(): AutowireInterface
{
    return new class implements AutowireInterface
    {
    };
}

function factory(?Closure $def = null): FactoryInterface
{
    return new class($def) implements FactoryInterface
    {
        private $def;
        public function __construct($def)
        {
            $this->def = $def;
        }
        public function hasDefinition(): bool
        {
            return $this->def !== null;
        }
        public function getDefinition(): Closure
        {
            assert($this->hasDefinition());
            return $this->def;
        }

        public function __invoke($container)
        {
            if ($this->def) {
                return ($this->def)($container);
            }
            return null;
        }
    };
}
