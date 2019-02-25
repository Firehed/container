<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

use Firehed\Container\EnvironmentVariableInterface;

class EnvironmentVariableValue implements CodeGeneratorInterface
{
    /** @var EnvironmentVariableInterface */
    private $env;

    public function __construct(EnvironmentVariableInterface $env)
    {
        $this->env = $env;
    }

    public function generateCode(string $functionName): string
    {
        $envVarName = $this->env->getName();
        return <<<PHP
protected function $functionName()
{
    \$value = getenv('$envVarName');
    if (\$value === false) {

    }
    return \$value;
}
PHP;
    }
}
