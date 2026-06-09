<?php

declare(strict_types=1);

namespace Firehed\Container;

use UnexpectedValueException;
use UnitEnum;

use function is_object;
use function sprintf;

/**
 * Definition wrapper for non-object types
 */
class ScalarDefinition implements DefinitionInterface
{
    public function __construct(private readonly mixed $value)
    {
        if (is_object($value) && !$value instanceof UnitEnum) {
            throw new UnexpectedValueException(
                'Only scalars and enums can be wrapped (got ' . get_debug_type($value) . ')',
            );
        }
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
