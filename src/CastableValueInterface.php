<?php

declare(strict_types=1);

namespace Firehed\Container;

/**
 * @internal for now, this may become public later
 * @phpstan-type Cast CastType|class-string<\BackedEnum>
 */
interface CastableValueInterface
{
    public function asBool(): CastableValueInterface;

    /**
     * @param class-string<\BackedEnum> $class
     */
    public function asEnum(string $class): CastableValueInterface;

    public function asFloat(): CastableValueInterface;

    public function asInt(): CastableValueInterface;

    /**
     * @internal
     * @return ?Cast
     */
    public function getCast(): CastType|string|null;
}
