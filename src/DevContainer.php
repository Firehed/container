<?php
declare(strict_types=1);

namespace Firehed\Container;

use Closure;
use Exception;
use Psr\Container;
use ReflectionClass;

class DevContainer implements Container\ContainerInterface
{
    /** @var mixed[] */
    private $definitions;

    /** @var mixed[] */
    private $evaluated = [];

    public function __construct(array $definitions)
    {
        $this->definitions = $definitions;
    }

    /**
     * Docblock types for interface adherence
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return array_key_exists($id, $this->definitions);
    }

    /**
     * Docblock types for interface adherence
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        if (array_key_exists($id, $this->evaluated)) {
            return $this->evaluated[$id];
        }

        if (!$this->has($id)) {
            throw new Exceptions\NotFound($id);
        }

        $def = $this->definitions[$id];

        if ($def instanceof AutowireInterface) {
            $value = $this->autowire($id);
        } else {
            $value = $def;
        }

        if ($value instanceof Closure) {
            $value = $value->bindTo(null);
            $evaluated = $value($this);
            $this->evaluated[$id] = $evaluated;

            return $evaluated;
        }

        if ($value instanceof FactoryInterface) {
            if ($value->hasDefinition()) {
                return $value->getDefinition()($this);
            }
            return $this->autowire($id)($this);
        }

        if ($value instanceof EnvironmentVariableInterface) {
            $varName = $value->getName();
            $envValue = getenv($varName);
            if ($envValue === false) {
                if ($value->hasDefault()) {
                    return $value->getDefault();
                }
                throw new Exceptions\EnvironmentVariableNotSet($varName);
            }
            return $envValue;
        }

        return $value;
    }

    /**
     * Returns a closure that takes the conatiner as its only argument and
     * returns the instantiated object
     */
    private function autowire(string $id): Closure
    {
        if (!class_exists($id)) {
            throw new \Exception('not a class');
        }
        $rc = new ReflectionClass($id);

        if (!$rc->hasMethod('__construct')) {
            return (function () use ($id) {
                return new $id();
            })->bindTo(null);
        }

        $construct = $rc->getMethod('__construct');
        if (!$construct->isPublic()) {
            throw new \Exception('non public construct');
        }

        $params = $construct->getParameters();
        $needed = [];
        foreach ($params as $param) {
            if (!$param->hasType()) {
                throw new Exceptions\UntypedValue($param->getName(), $id);
            }
            $type = $param->getType();
            assert($type !== null);
            $name = $type->getName();
            if (!$this->has($name)) {
                throw new Exceptions\UntypedValue($param->getName(), $id);
            }
            $needed[] = (function ($c) use ($name) {
                return $c->get($name);
            })->bindTo(null);
        }
        return (function ($container) use ($id, $needed) {
            $args = array_map(function ($arg) use ($container) {
                return $arg($container);
            }, $needed);
            return new $id(...$args);
        })->bindTo(null);
    }
}
