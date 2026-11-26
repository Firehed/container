<?php

declare(strict_types=1);

namespace Firehed\Container;

/**
 * A constructor parameter that autowiring fills from the container. Optional
 * parameters are only filled when the container has their type.
 *
 * @internal
 */
final class ConstructorArgument
{
    /** @param class-string $type */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly bool $isRequired,
    ) {
    }
}
