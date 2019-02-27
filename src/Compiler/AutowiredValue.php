<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

use Firehed\Container\Exceptions\UntypedValue;
use ReflectionClass;
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
            foreach ($params as $param) {
                if (!$param->hasType()) {
                    throw new UntypedValue($param->getName(), $this->class);
                }
                $type = $param->getType();
                assert($type !== null);
                if (!$this->isResolvableType($type)) {
                    throw new UntypedValue($param->getName(), $this->class);
                }
                $argClasses[] = $type->getName();
            }
            $args = array_map(function ($type) {
                return sprintf('$this->get(%s)', var_export($type, true));
            }, $argClasses);
        }

        $argInfo = implode(', ', $args);

        $code = <<<PHP
    return new {$this->class}(
        $argInfo
    );
PHP;
        return $code;
    }

    private function isResolvableType(ReflectionType $type): bool
    {
        return !$type->isBuiltin();
    }
}
