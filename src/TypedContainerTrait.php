<?php

declare(strict_types=1);

namespace Firehed\Container;

trait TypedContainerTrait
{
    public function getBool(string $id): bool
    {
        return $this->get($id);
    }

    public function getFloat(string $id): float
    {
        return $this->get($id);
    }

    public function getInt(string $id): int
    {
        return $this->get($id);
    }

    public function getString(string $id): string
    {
        return $this->get($id);
    }
}
