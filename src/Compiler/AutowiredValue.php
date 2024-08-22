<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

use BadMethodCallException;
use Firehed\Container\Exceptions\UntypedValue;
use ReflectionClass;
use ReflectionParameter;
use ReflectionNamedType;
use ReflectionType;

class AutowiredValue implements CodeGeneratorInterface
{
    /** @var class-string[] */
    private $dependencies;

    /** @param class-string $classToAutowire */
    public function __construct(private string $classToAutowire)
    {
        assert(class_exists($classToAutowire));
    }

    public function generateCode(): string
    {
        // This is initialized here rather than in the defintion to allow the
        // runtimeexception to be thrown
        $this->dependencies = [];

        $rc = new ReflectionClass($this->classToAutowire);
        $args = [];
        if ($rc->hasMethod('__construct')) {
            $argClasses = [];
            $constructor = $rc->getMethod('__construct');
            if (!$constructor->isPublic()) {
                throw new \Exception('not public construct');
            }
            $params = $constructor->getParameters();
            $args = array_map([$this, 'getDefaultValue'], $params);
        }

        $argInfo = implode(', ', $args);

        $code = <<<PHP
    return new {$this->classToAutowire}(
        $argInfo
    );
PHP;
        return $code;
    }

    public function getDependencies(): array
    {
        if ($this->dependencies === null) {
            throw new BadMethodCallException(__METHOD__ . ' can only be used after generateCode');
        }
        return $this->dependencies;
    }

    private function getDefaultValue(ReflectionParameter $param): string
    {
        if ($param->isOptional()) {
            return var_export($param->getDefaultValue(), true);
        }
        if (!$param->hasType()) {
            throw new UntypedValue($param->getName(), $this->classToAutowire);
        }
        $type = $param->getType();
        assert($type instanceof ReflectionType);
        // TODO: support ReflectionUnionType (#35), ReflectionIntersectionType (#36)?
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            throw new UntypedValue($param->getName(), $this->classToAutowire);
            // this would be good for non-builtins???
            // throw NotFound::autowireMissing($type->getName(), $this->classToAutowire, $param->getName());
        }
        /** @var class-string */
        $fqcn = $type->getName();
        $this->dependencies[] = $fqcn;
        return sprintf(
            '$this->get(%s)',
            var_export($type->getName(), true)
        );
    }
}
