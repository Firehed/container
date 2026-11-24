<?php

declare(strict_types=1);

namespace Firehed\Container;

use InvalidArgumentException;

use function enum_exists;
use function sprintf;

/**
 * @internal
 * @phpstan-import-type Cast from CastableValueInterface
 */
trait CastableValueTrait
{
    /** @var ?Cast */
    private CastType|string|null $cast = null;

    public function getCast(): CastType|string|null
    {
        return $this->cast;
    }

    public function asBool(): CastableValueInterface
    {
        $this->cast = CastType::Bool;
        return $this;
    }

    public function asEnum(string $class): CastableValueInterface
    {
        if (!enum_exists($class)) {
            throw new InvalidArgumentException(sprintf('Class for enum cast %s does not exist', $class));
        }
        // if !backedenum, fail
        $this->cast = $class;
        return $this;
    }

    public function asFloat(): CastableValueInterface
    {
        $this->cast = CastType::Float;
        return $this;
    }

    public function asInt(): CastableValueInterface
    {
        $this->cast = CastType::Int;
        return $this;
    }
}
