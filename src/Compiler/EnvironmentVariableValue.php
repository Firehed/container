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
        $cast = $this->env->getCast();
        return <<<PHP
\$value = getenv('$envVarName');
if (\$value === false) {
    {$this->getDefaultBody()}
}
{$this->castBody()}
PHP;
    }

    private function castBody(): string
    {
        $cast = $this->env->getCast();
        if ($cast === EnvironmentVariableInterface::CAST_NONE) {
            return 'return $value;';
        } elseif ($cast === EnvironmentVariableInterface::CAST_BOOL) {
            return <<<PHP
switch (strtolower(\$value)) {
    case '1':  // fallthrough
    case 'true':
        return true;
    case '': // fallthrough
    case '0':  // fallthrough
    case 'false':
        return false;
    default:
        throw new \OutOfBoundsException('Invalid boolean value');
}
PHP;
        } elseif ($cast === EnvironmentVariableInterface::CAST_INT) {
            return sprintf('return (%s)$value;', $cast);
        } elseif ($cast === EnvironmentVariableInterface::CAST_FLOAT) {
            return sprintf('return (%s)$value;', $cast);
        } else {
            // class-string<BackedEnum>
            return sprintf('return %s::from($value);', $cast);
        }
    }

    private function getDefaultBody(): string
    {
        if ($this->env->hasDefault()) {
            $default = var_export($this->env->getDefault(), true);
            return "\$value = $default;";
        } else {
            $varName = var_export($this->env->getName(), true);
            $exClass = EnvironmentVariableNotSet::class;
            return "throw new $exClass($varName);";
        }
    }
}
