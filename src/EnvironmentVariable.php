<?php
declare(strict_types=1);

namespace Firehed\Container;

use InvalidArgumentException;

use function enum_exists;
use function func_num_args;
use function sprintf;

/**
 * @internal - use `Firehed\Container\env()` to reach this.
 * @phpstan-import-type Cast from CastableValueInterface
 */
class EnvironmentVariable implements CastableValueInterface, DefinitionInterface
{
    /** @var ?Cast */
    private CastType|string|null $cast = null;

    private bool $hasDefault;

    public function __construct(private string $name, private ?string $default = null)
    {
        $this->hasDefault = func_num_args() === 2;
    }

    public function getCast(): CastType|string|null
    {
        return $this->cast;
    }

    public function asBool(): CastableValueInterface
    {
        $this->cast = CastType::Bool;
        return $this;
    }

    public function asEnum(string $class): CastableValueInterface
    {
        if (!enum_exists($class)) {
            throw new InvalidArgumentException(sprintf('Class for enum cast %s does not exist', $class));
        }
        // if !backedenum, fail
        $this->cast = $class;
        return $this;
    }

    public function asFloat(): CastableValueInterface
    {
        $this->cast = CastType::Float;
        return $this;
    }

    public function asInt(): CastableValueInterface
    {
        $this->cast = CastType::Int;
        return $this;
    }

    // DefinitionInterface implementation

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

    public function resolve(TypedContainerInterface $container, EnvReader $envReader): mixed
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
