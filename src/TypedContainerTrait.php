<?php

declare(strict_types=1);

namespace Firehed\Container;

use Firehed\Container\Exceptions\IncorrectlyTypedValue;

trait TypedContainerTrait
{
    public function getBool(string $id): bool
    {
        $value = $this->get($id);
        if (is_bool($value)) {
            return $value;
        }
        throw new IncorrectlyTypedValue($id, 'bool', gettype($value));
    }

    public function getFloat(string $id): float
    {
        $value = $this->get($id);
        if (is_float($value)) {
            return $value;
        }
        throw new IncorrectlyTypedValue($id, 'float', gettype($value));
    }

    public function getInt(string $id): int
    {
        $value = $this->get($id);
        if (is_int($value)) {
            return $value;
        }
        throw new IncorrectlyTypedValue($id, 'int', gettype($value));
    }

    public function getString(string $id): string
    {
        $value = $this->get($id);
        if (is_string($value)) {
            return $value;
        }
        throw new IncorrectlyTypedValue($id, 'string', gettype($value));
    }
}
