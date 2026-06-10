<?php

declare(strict_types=1);

namespace Firehed\Container;

/**
 * Maps one container key to another (e.g., interface to implementation).
 */
class AliasDefinition implements DefinitionInterface
{
    /**
     * @param class-string $target
     */
    public function __construct(private readonly string $target)
    {
    }

    public function generateCode(): string
    {
        return sprintf('return $this->get(%s);', var_export($this->target, true));
    }

    /**
     * @return class-string[]
     */
    public function getDependencies(): array
    {
        return [$this->target];
    }

    public function isCacheable(): bool
    {
        // This is set as non-cacheable since the target value itself may also
        // be non-cacheable, and there's not a _great_ way to know whether the
        // target is or isn't with the available data.
        return false;
    }

    public function resolve(TypedContainerInterface $container, EnvReader $envReader): mixed
    {
        return $container->get($this->target);
    }
}
