<?php
declare(strict_types=1);

namespace Firehed\Container;

use BadMethodCallException;

/**
 * @internal for now, this may become public later
 */
interface EnvironmentVariableInterface extends CastableValueInterface
{
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
