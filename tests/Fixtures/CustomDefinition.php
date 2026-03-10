<?php

declare(strict_types=1);

namespace Firehed\Container\Fixtures;

use Firehed\Container\DefinitionInterface;
use Firehed\Container\EnvReader;
use Firehed\Container\TypedContainerInterface;

/**
 * Test fixture implementing DefinitionInterface.
 * Simulates a third-party custom definition.
 */
class CustomDefinition implements DefinitionInterface
{
    public function __construct(
        private mixed $value,
        private bool $cacheable = true,
    ) {
    }

    public function generateCode(): string
    {
        return sprintf('return %s;', var_export($this->value, true));
    }

    /** @return class-string[] */
    public function getDependencies(): array
    {
        return [];
    }

    public function resolve(TypedContainerInterface $container, EnvReader $envReader): mixed
    {
        return $this->value;
    }

    public function isCacheable(): bool
    {
        return $this->cacheable;
    }
}
