<?php
declare(strict_types=1);

namespace Firehed\Container;

interface FactoryInterface
{
    public function hasDefinition(): bool;

    /**
     * Sets the class to autowire when there's no closure definition.
     *
     * @param class-string $class
     */
    public function withClass(string $class): self;
}
