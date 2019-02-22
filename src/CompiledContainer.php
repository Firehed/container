<?php
declare(strict_types=1);

namespace Firehed\Container;

use Psr\Container\ContainerInterface;

abstract class CompiledContainer implements ContainerInterface
{
    protected $mappings = [];

    /**
     * @param string $id
     */
    public function has($id): bool
    {
        return array_key_exists($id, $this->mappings);
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        // if !has throw

        $function = $this->mappings[$id];
        return [$this, $function]();
    }
}
