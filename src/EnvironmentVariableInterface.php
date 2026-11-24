<?php
declare(strict_types=1);

namespace Firehed\Container;

use BadMethodCallException;

/**
 * @internal for now, this may become public later
 */
interface EnvironmentVariableInterface extends CastableValueInterface
{
    public function asBool(): EnvironmentVariableInterface;

    public function asFloat(): EnvironmentVariableInterface;

    /**
     * @param class-string<\BackedEnum> $class
     */
    public function asEnum(string $class): EnvironmentVariableInterface;

    public function asInt(): EnvironmentVariableInterface;

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
