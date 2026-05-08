<?php

declare(strict_types=1);

namespace Firehed\Container;

/**
 * A definition that can be bound to a class.
 *
 * Definitions created without an explicit class (e.g., `factory()` or
 * `autowire()` with no arguments) implement this interface. The builder
 * will call `withClass()` using the array key from the definition file.
 */
interface ClassBindable
{
    /**
     * Returns true if this definition needs a class to be set via withClass().
     */
    public function needsClass(): bool;

    /**
     * Returns a copy of this definition bound to the specified class.
     *
     * @param class-string $class
     */
    public function withClass(string $class): self;
}
