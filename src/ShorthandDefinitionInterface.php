<?php

declare(strict_types=1);

namespace Firehed\Container;

/**
 * A definition that supports shorthand syntax.
 *
 * Definitions created without an explicit class (e.g., `factory()` or
 * `autowire()` with no arguments) implement this interface. The builder
 * will call `withClass()` using the array key from the definition file.
 *
 * This enables shorthand definitions, avoiding the need for:
 *
 *     `Foo::class => someTypeWrapper(Foo::class)`
 *
 * and instead allowing:
 *
 *     `Foo::class => someTypeWrapper()`
 */
interface ShorthandDefinitionInterface extends DefinitionInterface
{
    /**
     * Returns true if this definition needs a class to be set via withClass().
     * This is called during container building/compilation, not runtime.
     */
    public function needsClass(): bool;

    /**
     * Returns a definition for the specified class. The class will be provided
     * based on the config key, and is guaranteed to pass `class_exists()`.
     *
     * @param class-string $class
     */
    public function withClass(string $class): DefinitionInterface;
}
