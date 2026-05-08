<?php

declare(strict_types=1);

namespace Firehed\Container;

/**
 * A definition that can be bound to a class.
 *
 * Definitions created without an explicit class (e.g., `factory()` or
 * `autowire()` with no arguments) implement this interface. The builder
 * will call `withClass()` using the array key from the definition file.
 *
 * This is to enable shorthand function defintions, e.g. avoiding the need for:
 *
 *     `Foo::class => someTypeWrapper(Foo::class)`
 *
 * and instead allowing:
 *
 *     `Foo::class => someTypeWrapper()`
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
