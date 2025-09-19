<?php

declare(strict_types=1);

namespace Firehed\Container;

use Psr\Container\ContainerInterface;

interface TypedContainerInterface extends ContainerInterface
{
    /**
     * @template T
     * @param string $id
     * @return ($id is class-string<T> ? T : mixed)
     */
    public function get($id);

    public function getBool(string $id): bool;

    public function getFloat(string $id): float;

    public function getInt(string $id): int;

    public function getString(string $id): string;
}
