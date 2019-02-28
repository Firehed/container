<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

use Firehed\Container\EnvironmentVariableInterface;

use Firehed\Container\Exceptions\EnvironmentVariableNotSet;

class EnvironmentVariableValue implements CodeGeneratorInterface
{
    use NoDependenciesTrait;

    /** @var EnvironmentVariableInterface */
    private $env;

    public function __construct(EnvironmentVariableInterface $env)
    {
        $this->env = $env;
    }

    public function generateCode(): string
    {
        $envVarName = $this->env->getName();
        return <<<PHP
\$value = getenv('$envVarName');
if (\$value === false) {
    {$this->getDefaultBody()}
}
return \$value;
PHP;
    }

    private function getDefaultBody(): string
    {
        if ($this->env->hasDefault()) {
            $default = var_export($this->env->getDefault(), true);
            return "return $default;";
        } else {
            $varName = var_export($this->env->getName(), true);
            $exClass = EnvironmentVariableNotSet::class;
            return "throw new $exClass($varName);";
        }
    }
}
