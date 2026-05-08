<?php

declare(strict_types=1);

namespace Firehed\Container;

class ScalarDefinition implements DefinitionInterface
{
    public function __construct(private readonly int|bool|string|float $value)
    {
    }

    public function generateCode(): string
    {
        return sprintf('return %s;', var_export($this->value, true));
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function isCacheable(): bool
    {
        return true;
    }

    public function resolve(TypedContainerInterface $container, EnvReader $envReader): mixed
    {
        return $this->value;
    }
}
