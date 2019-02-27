<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

use Firehed\Container\Exceptions\UntypedValue;
use ReflectionClass;
use ReflectionParameter;
use ReflectionType;

class AutowiredValue implements CodeGeneratorInterface
{
    /** @var string FQCN */
    private $class;

    public function __construct(string $classToAutowire)
    {
        if (!class_exists($classToAutowire)) {
            // throw
        }
        $this->class = $classToAutowire;
    }

    public function generateCode(): string
    {
        $rc = new ReflectionClass($this->class);
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
    return new {$this->class}(
        $argInfo
    );
PHP;
        return $code;
    }

    private function isResolvableParam(ReflectionParameter $param): bool
    {
        if ($param->isOptional()) {
            return true;
        }
        if ($param->hasType()) {
            $type = $param->getType();
            assert($type !== null);
            return !$type->isBuiltin();
        }
        return false;
    }

    private function getDefaultValue(ReflectionParameter $param): string
    {
        if (!$this->isResolvableParam($param)) {
            throw new UntypedValue($param->getName(), $this->class);
        }
        if ($param->isOptional()) {
            return var_export($param->getDefaultValue(), true);
        }
        $type = $param->getType();
        assert($type !== null);
        return sprintf(
            '$this->get(%s)',
            var_export($type->getName(), true)
        );
    }
}
