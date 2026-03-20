<?php
declare(strict_types=1);

namespace Firehed\Container;

use Closure;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use Throwable;

class DevContainer implements TypedContainerInterface
{
    use TypedContainerTrait;

    /** @var mixed[] */
    private $evaluated = [];

    /** @param mixed[] $definitions */
    public function __construct(private array $definitions, private EnvReader $envReader)
    {
    }

    /** @return array{ids: list<string>} */
    public function __debugInfo(): array
    {
        $ids = array_keys($this->definitions);
        sort($ids);
        return ['ids' => $ids];
    }

    public function has($id): bool
    {
        return array_key_exists($id, $this->definitions);
    }

    public function get($id)
    {
        try {
            return $this->doGet($id);
        } catch (Throwable $e) {
            if ($e instanceof Exceptions\ValueRetrievalException) {
                $e->addId($id);
            }
            if ($e instanceof ContainerExceptionInterface) {
                // If it's a known (i.e. internal) exception, rethrow it
                throw $e;
            }
            // Repackage the error into something with a more helpful message
            throw new Exceptions\ValueRetrievalException($id, $e);
        }
    }

    /**
     * @param string $id
     * @return mixed
     */
    private function doGet($id)
    {
        if (array_key_exists($id, $this->evaluated)) {
            return $this->evaluated[$id];
        }

        if (!$this->has($id)) {
            throw new Exceptions\NotFound($id);
        }

        $def = $this->definitions[$id];

        if ($def instanceof DefinitionInterface) {
            $result = $def->resolve($this, $this->envReader);
            if ($def->isCacheable()) {
                $this->evaluated[$id] = $result;
            }
            return $result;
        }

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

        return $value;
    }

    /**
     * Returns a closure that takes the container as its only argument and
     * returns the instantiated object
     */
    private function autowire(string $class): Closure
    {
        if (!class_exists($class)) {
            throw new Exceptions\AmbiguousMapping($class);
        }
        $rc = new ReflectionClass($class);

        if (!$rc->hasMethod('__construct')) {
            return (function () use ($class) {
                return new $class();
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
                $name = Autowire::getRequiredDependencyType($param, $class);
                if (!$this->has($name)) {
                    throw Exceptions\NotFound::autowireMissing($name, $class, $param->getName());
                }
                $needed[] = (function (TypedContainerInterface $c) use ($name) {
                    return $c->get($name);
                })->bindTo(null);
            }
        }
        return (function (TypedContainerInterface $container) use ($class, $needed) {
            $args = array_map(function ($arg) use ($container) {
                return $arg($container);
            }, $needed);
            return new $class(...$args);
        })->bindTo(null);
    }
}
