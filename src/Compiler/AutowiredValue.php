<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

use BadMethodCallException;
use Firehed\Container\Autowire;
use ReflectionClass;
use ReflectionParameter;

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
        // This is initialized here rather than in the definition to allow the
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
        $fqcn = Autowire::getRequiredDependencyType($param, $this->classToAutowire);
        $this->dependencies[] = $fqcn;
        return sprintf(
            '$this->get(%s)',
            var_export($fqcn, true)
        );
    }
}
