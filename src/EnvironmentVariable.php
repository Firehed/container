<?php
declare(strict_types=1);

namespace Firehed\Container;

use function func_num_args;
use function sprintf;

/**
 * @internal - use `Firehed\Container\env()` to reach this.
 */
class EnvironmentVariable implements CastableValueInterface, DefinitionInterface
{
    use CastableValueTrait;

    private bool $hasDefault;

    public function __construct(private string $name, private ?string $default = null)
    {
        $this->hasDefault = func_num_args() === 2;
    }

    public function generateCode(): string
    {
        return <<<PHP
\$value = \$this->envReader->read('{$this->name}');
if (\$value === null) {
    {$this->getDefaultCodeBody()}
}
return \$value;
PHP;
    }

    private function getDefaultCodeBody(): string
    {
        if ($this->hasDefault) {
            $default = var_export($this->default, true);
            return "\$value = $default;";
        } else {
            $varName = var_export($this->name, true);
            return sprintf('throw new %s(%s);', Exceptions\EnvironmentVariableNotSet::class, $varName);
        }
    }

    /** @return class-string[] */
    public function getDependencies(): array
    {
        return [];
    }

    public function resolve(TypedContainerInterface $container, EnvReader $envReader): ?string
    {
        $envValue = $envReader->read($this->name);
        if ($envValue === null) {
            if ($this->hasDefault) {
                $envValue = $this->default;
            } else {
                throw new Exceptions\EnvironmentVariableNotSet($this->name);
            }
        }

        return $envValue;
    }

    public function isCacheable(): bool
    {
        return true;
    }
}
