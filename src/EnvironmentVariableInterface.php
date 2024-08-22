<?php
declare(strict_types=1);

namespace Firehed\Container;

use BadMethodCallException;

interface EnvironmentVariableInterface
{
    public const CAST_BOOL = 'bool';
    public const CAST_FLOAT = 'float';
    public const CAST_INT = 'int';
    public const CAST_NONE = '';

    public function asBool(): EnvironmentVariableInterface;

    public function asFloat(): EnvironmentVariableInterface;

    /**
     * @param class-string<\BackedEnum> $class
     */
    public function asEnum(string $class): EnvironmentVariableInterface;

    public function asInt(): EnvironmentVariableInterface;

    /**
     * @internal
     * Return the casting type: bool, int, float, '', or the class-string of
     * a BackedEnum.
     */
    public function getCast(): string;

    /**
     * @internal
     * @throws BadMethodCallException if hasDefault would return false
     */
    public function getDefault(): ?string;

    /**
     * @internal
     */
    public function getName(): string;

    /**
     * @internal
     */
    public function hasDefault(): bool;
}
