<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

use BadMethodCallException;
use Firehed\Container\Autowire;
use Firehed\Container\ConstructorArgument;

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

        $lines = ['$args = [];'];
        foreach (Autowire::getConstructorArguments($this->classToAutowire) as $argument) {
            $lines[] = $this->generateArgument($argument);
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
     * Defaults are never evaluated here: `new` in an initializer would run at
     * compile time and could not be exported.
     */
    private function generateArgument(ConstructorArgument $argument): string
    {
        $name = var_export($argument->name, true);
        $type = var_export($argument->type, true);
        if ($argument->isRequired) {
            $this->dependencies[] = $argument->type;
            return sprintf('$args[%s] = $this->get(%s);', $name, $type);
        }
        return sprintf('if ($this->has(%1$s)) { $args[%2$s] = $this->get(%1$s); }', $type, $name);
    }
}
