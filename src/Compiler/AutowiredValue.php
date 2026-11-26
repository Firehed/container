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
        $lines = ['$args = [];'];
        if ($rc->hasMethod('__construct')) {
            $constructor = $rc->getMethod('__construct');
            if (!$constructor->isPublic()) {
                throw new \Exception('not public construct');
            }
            $lines = [...$lines, ...$this->generateArguments($constructor->getParameters())];
        }
        $lines[] = sprintf('return new %s(...$args);', $this->classToAutowire);

        return implode("\n", $lines);
    }

    public function getDependencies(): array
    {
        if ($this->dependencies === null) {
            throw new BadMethodCallException(__METHOD__ . ' can only be used after generateCode');
        }
        return $this->dependencies;
    }

    /**
     * Optional parameters are left out unless the container can fill them,
     * so PHP applies the declared default at runtime. Defaults are never
     * evaluated here: `new` in an initializer would run at compile time and
     * could not be exported. Arguments are passed by name so that leaving one
     * out does not shift the rest.
     *
     * @param ReflectionParameter[] $params
     * @return string[]
     */
    private function generateArguments(array $params): array
    {
        $lines = [];
        foreach ($params as $param) {
            $name = var_export($param->getName(), true);
            if ($param->isOptional()) {
                $typeName = Autowire::getOptionalDependencyType($param, $this->classToAutowire);
                if ($typeName !== null) {
                    $lines[] = sprintf(
                        'if ($this->has(%1$s)) { $args[%2$s] = $this->get(%1$s); }',
                        var_export($typeName, true),
                        $name,
                    );
                }
            } else {
                $fqcn = Autowire::getRequiredDependencyType($param, $this->classToAutowire);
                $this->dependencies[] = $fqcn;
                $lines[] = sprintf('$args[%s] = $this->get(%s);', $name, var_export($fqcn, true));
            }
        }
        return $lines;
    }
}
