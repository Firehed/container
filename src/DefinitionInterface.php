<?php

declare(strict_types=1);

namespace Firehed\Container;

/**
 * Interface for container value definitions.
 *
 * Implementations define how a container entry is resolved, both at runtime
 * (for DevContainer) and at compile-time (for compiled containers).
 *
 * Third-party libraries can implement this interface to provide custom
 * value resolution strategies (e.g., fetching secrets from a vault).
 */
interface DefinitionInterface extends Compiler\CodeGeneratorInterface
{
    /**
     * Resolve the value at runtime.
     *
     * @param TypedContainerInterface $container The container instance
     * @param EnvReader $envReader Environment variable reader
     * @return mixed The resolved value
     */
    public function resolve(TypedContainerInterface $container, EnvReader $envReader): mixed;

    /**
     * Whether the resolved value should be cached.
     *
     * Return false for factory definitions where each call should produce
     * a new instance.
     */
    public function isCacheable(): bool;
}
