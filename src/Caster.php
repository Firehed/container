<?php

declare(strict_types=1);

namespace Firehed\Container;

use UnexpectedValueException;

use function is_string;

/**
 * @internal
 */
class Caster
{
    /**
     * @param CastableValueInterface::CAST_*|class-string<\BackedEnum> $cast
     */
    public function cast(mixed $value, string $cast): mixed
    {
        if ($value !== null && !is_string($value)) {
            throw new UnexpectedValueException('Castable values must resolve to a string or null');
        }
        return match ($cast) {
            CastableValueInterface::CAST_NONE => $value,
            CastableValueInterface::CAST_BOOL => EnvironmentVariable::parseBool((string) $value),
            CastableValueInterface::CAST_INT => (int) $value,
            CastableValueInterface::CAST_FLOAT => (float) $value,
            // @phpstan-ignore argument.type (null intentionally triggers native TypeError)
            default => $cast::from($value),
        };
    }
}
