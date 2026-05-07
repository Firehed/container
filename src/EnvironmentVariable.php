<?php
declare(strict_types=1);

namespace Firehed\Container;

use InvalidArgumentException;

use function enum_exists;
use function func_num_args;
use function sprintf;

class EnvironmentVariable implements EnvironmentVariableInterface, DefinitionInterface
{
    /** @var EnvironmentVariableInterface::CAST_* | class-string<\BackedEnum> */
    private string $cast = EnvironmentVariableInterface::CAST_NONE;

    private bool $hasDefault;

    public function __construct(private string $name, private ?string $default = null)
    {
        $this->hasDefault = func_num_args() === 2;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }

    public function getDefault(): ?string
    {
        if (!$this->hasDefault) {
            throw new Exceptions\EnvironmentVariableNotSet($this->name);
        }
        return $this->default;
    }

    public function getCast(): string
    {
        return $this->cast;
    }

    public function asBool(): EnvironmentVariableInterface
    {
        $this->cast = EnvironmentVariableInterface::CAST_BOOL;
        return $this;
    }

    public function asEnum(string $class): EnvironmentVariableInterface
    {
        if (!enum_exists($class)) {
            throw new InvalidArgumentException(sprintf('Class for enum cast %s does not exist', $class));
        }
        // if !backedenum, fail
        $this->cast = $class;
        return $this;
    }

    public function asFloat(): EnvironmentVariableInterface
    {
        $this->cast = EnvironmentVariableInterface::CAST_FLOAT;
        return $this;
    }

    public function asInt(): EnvironmentVariableInterface
    {
        $this->cast = EnvironmentVariableInterface::CAST_INT;
        return $this;
    }

    public static function parseBool(string $value): bool
    {
        return match (strtolower($value)) {
            '1', 'true' => true,
            '', '0', 'false' => false,
            default => throw new \OutOfBoundsException('Invalid boolean value'),
        };
    }

    // DefinitionInterface implementation

    public function generateCode(): string
    {
        return <<<PHP
\$value = \$this->envReader->read('{$this->name}');
if (\$value === null) {
    {$this->getDefaultCodeBody()}
}
{$this->getCastCodeBody()}
PHP;
    }

    private function getCastCodeBody(): string
    {
        return match ($this->cast) {
            EnvironmentVariableInterface::CAST_NONE => 'return $value;',
            EnvironmentVariableInterface::CAST_BOOL => sprintf(
                'return %s::parseBool($value);',
                self::class,
            ),
            EnvironmentVariableInterface::CAST_INT,
            EnvironmentVariableInterface::CAST_FLOAT => sprintf('return (%s)$value;', $this->cast),
            default => sprintf('return %s::from($value);', $this->cast),
        };
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

        return match ($this->cast) {
            EnvironmentVariableInterface::CAST_NONE => $envValue,
            EnvironmentVariableInterface::CAST_BOOL => self::parseBool((string) $envValue),
            EnvironmentVariableInterface::CAST_INT => (int) $envValue,
            EnvironmentVariableInterface::CAST_FLOAT => (float) $envValue,
            // Remaining cast type is an enum; use its `::from` method
            // @phpstan-ignore argument.type (null intentionally triggers native TypeError)
            default => $this->cast::from($envValue),
        };
    }

    public function isCacheable(): bool
    {
        return true;
    }
}
