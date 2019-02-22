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

    public function generateCode(string $functionName): string
    {
        $dest = sprintf('$this->get(%s)', var_export($this->class, true));
        $code = <<<PHP
protected function $functionName()
{
    return $dest;
}
PHP;
        return $code;
    }
}
