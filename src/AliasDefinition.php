<?php

declare(strict_types=1);

namespace Firehed\Container;

/**
 * Maps one container key to another (e.g., interface to implementation).
 *
 * Acts as a factory so that the cacheability of the underlying value is
 * preserved.
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
        return false;
    }

    public function resolve(TypedContainerInterface $container, EnvReader $envReader): mixed
    {
        return $container->get($this->target);
    }
}
