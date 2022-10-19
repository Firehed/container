<?php
declare(strict_types=1);

namespace Firehed\Container;

use Throwable;
use Psr\Container\ContainerExceptionInterface;

abstract class CompiledContainer implements TypedContainerInterface
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

    public function has($id): bool
    {
        return array_key_exists($id, $this->mappings);
    }

    public function get($id)
    {
        try {
            return $this->doGet($id);
        } catch (Throwable $e) {
            if ($e instanceof Exceptions\ValueRetreivalException) {
                $e->addId($id);
            }
            if ($e instanceof ContainerExceptionInterface) {
                // If it's a known (i.e. internal) exception, rethrow it
                throw $e;
            }
            // Repackage the error into something with a more helpful message
            throw new Exceptions\ValueRetreivalException($id, $e);
        }
    }

    /**
     * @param string $id
     * @return mixed
     */
    private function doGet($id)
    {
        if (!$this->has($id)) {
            throw new Exceptions\NotFound($id);
        }

        // Check the value cache
        if (isset($this->values[$id])) {
            return $this->values[$id];
        }

        /** @var callable */
        $function = [$this, $this->mappings[$id]];
        $result = $function();
        if (isset($this->factories[$id])) {
            // Do not insert into value cache
        } else {
            $this->values[$id] = $result;
        }
        return $result;
    }
}
