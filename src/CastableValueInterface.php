<?php

declare(strict_types=1);

namespace Firehed\Container;

/**
 * @internal for now, this may become public later
 * @phpstan-type Cast CastType|class-string<\BackedEnum>
 */
interface CastableValueInterface
{
    public const CAST_BOOL = 'bool';
    public const CAST_FLOAT = 'float';
    public const CAST_INT = 'int';
    public const CAST_NONE = '';

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
