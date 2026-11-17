<?php

declare(strict_types=1);

namespace Firehed\Container;

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
}
