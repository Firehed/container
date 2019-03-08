<?php
declare(strict_types=1);

namespace Firehed\Container;

use BadMethodCallException;

interface EnvironmentVariableInterface
{
    public function asBool(): EnvironmentVariableInterface;

    public function asInt(): EnvironmentVariableInterface;

    public function asFloat(): EnvironmentVariableInterface;

    /**
     * @internal
     * Return the casting type: string, bool, int, or float
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
