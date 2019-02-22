<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

use ReflectionClass;

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

    public function generateCode(string $functionName): string
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
                    var_dump($this->class);
                    exit;
                    throw new \Exception('untyped constructor param');
                }
                $type = $param->getType();
                assert($type !== null);
                $name = $type->getName();
                // validate exists?
                $argClasses[] = $name;
            }
            $args = array_map(function ($type) {
                return sprintf('$this->get(%s)', var_export($type, true));
            }, $argClasses);
        }

        $argInfo = implode(', ', $args);

        $code = <<<PHP
protected function $functionName()
{
    return new {$this->class}(
        $argInfo
    );
}
PHP;
        return $code;
    }
}
