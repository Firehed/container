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
     * @param ?Cast $cast
     */
    public function cast(mixed $value, CastType|string|null $cast): mixed
    {
        if ($cast === null) {
            return $value;
        }
        if ($value !== null && !is_string($value)) {
            throw new UnexpectedValueException('Castable values must resolve to a string or null');
        }
        if ($cast instanceof CastType) {
            return match ($cast) {
                CastType::Bool => $this->parseBool((string) $value),
                CastType::Float => (float) $value,
                CastType::Int => (int) $value,
            };
        }
        // @phpstan-ignore argument.type (null intentionally triggers native TypeError)
        return $cast::from($value);
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
