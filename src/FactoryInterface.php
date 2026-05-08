<?php
declare(strict_types=1);

namespace Firehed\Container;

use Closure;

interface FactoryInterface
{
    public function getDefinition(): Closure;
    public function hasDefinition(): bool;

    /**
     * Sets the class to autowire when there's no closure definition.
     *
     * @param class-string $class
     */
    public function withClass(string $class): self;
}
