<?php
declare(strict_types=1);

namespace Firehed\Container;

use Exception;
use Psr\Container;
use ReflectionClass;

class DevContainer implements Container\ContainerInterface
{
    private $definitions;

    private $evaluated = [];

    public function __construct(array $definitions)
    {
        $this->definitions = $definitions;
    }

    public function has($id)
    {
        return array_key_exists($id, $this->definitions);
    }

    public function get($id)
    {
        if (!$this->has($id)) {
            /// throw
        }

        if (array_key_exists($id, $this->evaluated)) {
            return $this->evaluated[$id];
        }

        $def = $this->definitions[$id];

        if ($def instanceof AutowireInterface) {
            $value = $this->autowire($id);
        } else {
            $value = $def;
            // var_dump($def);
        }

        if ($value instanceof \Closure) {
            return $value($this);
        }
        // var_dump($value);
        return $value;
    }

    private function autowire($id)
    {
        if (!class_exists($id)) {
            throw new \Exception('not a class');
        }
        $rc = new ReflectionClass($id);

        if (!$rc->hasMethod('__construct')) {
            return function () use ($id) {
                return new $id();
            };
        }

        $construct = $rc->getMethod('__construct');
        if (!$construct->isPublic()) {
            throw new \Exception('non public construct');
        }

        $params = $construct->getParameters();
        $needed = [];
        foreach ($params as $param) {
            if (!$param->hasType()) {
                throw new \Exception('untyped constructor param');
            }
            $type = $param->getType();
            assert($type !== null);
            $name = $type->getName();
            if (!$this->has($name)) {
                throw new \Exception('undefined type in constructor param');
            }
            $needed[] = $name;
            // var_dump($name);
        }
        return function ($container) use ($id, $needed) {
            $args = array_map(function ($arg) use ($container) {
                return $container->get($arg);
            }, $needed);
            return new $id(...$args);
        };
    }
}
