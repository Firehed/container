<?php
declare(strict_types=1);

namespace Firehed\Container;

use BadMethodCallException;

interface EnvironmentVariableInterface
{
    /** @throws BadMethodCallException if hasDefault would return false */
    public function getDefault(): ?string;

    public function getName(): string;

    public function hasDefault(): bool;
}
