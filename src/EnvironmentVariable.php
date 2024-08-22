<?php
declare(strict_types=1);

namespace Firehed\Container;

use InvalidArgumentException;

use function enum_exists;
use function func_num_args;
use function sprintf;

class EnvironmentVariable implements EnvironmentVariableInterface
{
    /** @var EnvironmentVariableInterface::CAST_* | class-string<\BackedEnum> */
    private $cast = EnvironmentVariableInterface::CAST_NONE;
    private ?string $default;
    private bool $hasDefault;
    private string $name;

    public function __construct(string $name, ?string $default = null)
    {
        $this->name = $name;
        $this->hasDefault = func_num_args() === 2;
        $this->default = $default;
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
}
