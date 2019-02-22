<?php

namespace Firehed\Container;

function autowire()
{
    return new class implements AutowireInterface
    {
    };
}

function factory(\Closure $def = null)
{
    return new class($def) implements FactoryInterface
    {
        private $def;
        public function __construct($def)
        {
            $this->def = $def;
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
