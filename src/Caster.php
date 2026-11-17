<?php

declare(strict_types=1);

namespace Firehed\Container;

use OutOfBoundsException;
use UnexpectedValueException;

use function is_string;
use function strtolower;

/**
 * @internal
 * @phpstan-import-type Cast from CastableValueInterface
 */
class Caster
{
    /**
     * @param Cast $cast
     */
    public function cast(mixed $value, string $cast): mixed
    {
        if ($cast === CastableValueInterface::CAST_NONE) {
            return $value;
        }
        if ($value !== null && !is_string($value)) {
            throw new UnexpectedValueException('Castable values must resolve to a string or null');
        }
        return match ($cast) {
            CastableValueInterface::CAST_BOOL => $this->parseBool((string) $value),
            CastableValueInterface::CAST_INT => (int) $value,
            CastableValueInterface::CAST_FLOAT => (float) $value,
            // @phpstan-ignore argument.type (null intentionally triggers native TypeError)
            default => $cast::from($value),
        };
    }

    private function parseBool(string $value): bool
    {
        return match (strtolower($value)) {
            '1', 'true' => true,
            '', '0', 'false' => false,
            default => throw new OutOfBoundsException('Invalid boolean value'),
        };
    }
}
