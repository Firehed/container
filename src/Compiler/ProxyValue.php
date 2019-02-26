<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

class ProxyValue implements CodeGeneratorInterface
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
        return sprintf(
            'return $this->get(%s);',
            var_export($this->class, true)
        );
    }
}
