<?php
declare(strict_types=1);

namespace Firehed\Container;

use Closure;
use Exception;
use Psr\Container;
use ReflectionClass;
use ReflectionNamedType;

class DevContainer implements Container\ContainerInterface
{
    /** @var mixed[] */
    private $definitions;

    /** @var mixed[] */
    private $evaluated = [];

    /** @param mixed[] $definitions */
    public function __construct(array $definitions)
    {
        $this->definitions = $definitions;
    }

    /**
     * Docblock types for interface adherence
     * @param string $id
     */
    public function has($id): bool
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
            $classToAutowire = $def->getWiredClass() ?? $id;
            $value = $this->autowire($classToAutowire);
        } else {
            $value = $def;
        }

        if ($value instanceof Closure) {
            $rebound = $value->bindTo(null);
            assert($rebound !== null);
            $evaluated = $rebound($this);
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
                    $envValue = $value->getDefault();
                } else {
                    throw new Exceptions\EnvironmentVariableNotSet($varName);
                }
            }
            $cast = $value->getCast();
            switch ($cast) {
                case '':
                    return $envValue;
                case 'bool':
                    switch (strtolower((string)$envValue)) {
                        case '1': // fallthrough
                        case 'true':
                            return true;
                        case '': // fallthrough
                        case '0': // fallthrough
                        case 'false':
                            return false;
                        default:
                            throw new \OutOfBoundsException('Invalid boolean value');
                    }
                    // comment line for phpcs, otherwise irrelevant
                case 'int':
                    return (int) $envValue;
                case 'float':
                    return (float) $envValue;
                default:
                    throw new \DomainException('Invalid cast ' . $cast);
            }
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
            throw new Exceptions\AmbiguousMapping($id);
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
            if ($param->isOptional()) {
                $needed[] = function () use ($param) {
                    return $param->getDefaultValue();
                };
            } else {
                if (!$param->hasType()) {
                    throw new Exceptions\UntypedValue($param->getName(), $id);
                }
                $type = $param->getType();
                assert($type !== null);
                assert($type instanceof ReflectionNamedType);
                if ($type->isBuiltin()) {
                    throw new Exceptions\UntypedValue($param->getName(), $id);
                }
                $name = $type->getName();
                if (!$this->has($name)) {
                    throw new Exceptions\NotFound($param->getName());
                }
                $needed[] = (function ($c) use ($name) {
                    return $c->get($name);
                })->bindTo(null);
            }
        }
        return (function ($container) use ($id, $needed) {
            $args = array_map(function ($arg) use ($container) {
                return $arg($container);
            }, $needed);
            return new $id(...$args);
        })->bindTo(null);
    }
}
