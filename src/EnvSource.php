<?php

declare(strict_types=1);

namespace Firehed\Container;

use function sprintf;
use function var_export;

/**
 * @internal
 */
class EnvSource implements ValueSourceInterface
{
    public function __construct(private string $name)
    {
    }

    public function generateReadExpression(): string
    {
        return sprintf('$this->envReader->read(%s)', var_export($this->name, true));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function read(EnvReader $envReader): ?string
    {
        return $envReader->read($this->name);
    }
}
