<?php
declare(strict_types=1);

namespace Firehed\Container;

use Psr\Container\ContainerInterface;

abstract class CompiledContainer implements ContainerInterface
{
    /**
     * Tracks what $id keys correspond to factory definitions and must not be
     * cached
     * @var array<string, true>
     */
    protected $factories = [];

    /**
     * Holds computed values from non-factory definitions
     * @var array<string, mixed>
     */
    private $values = [];

    /**
     * Maps $id keys to the internal function
     * @var array<string, string>
     */
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

        // Check the value cache
        if (isset($this->values[$id])) {
            return $this->values[$id];
        }

        $function = $this->mappings[$id];
        $result = [$this, $function]();
        if (isset($this->factories[$id])) {
            // Do not insert into value cache
        } else {
            $this->values[$id] = $result;
        }
        return $result;
    }
}
