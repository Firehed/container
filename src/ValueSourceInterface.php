<?php

declare(strict_types=1);

namespace Firehed\Container;

/**
 * @internal
 */
interface ValueSourceInterface
{
    /**
     * A PHP expression equivalent to read(), evaluated inside a compiled
     * container where `$this->envReader` is in scope
     */
    public function generateReadExpression(): string;

    /**
     * Used to identify the source when no value is found
     */
    public function getName(): string;

    /**
     * Returns null when the source has no value
     */
    public function read(EnvReader $envReader): ?string;
}
