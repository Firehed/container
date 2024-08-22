<?php
declare(strict_types=1);

namespace Firehed\Container;

class EnvironmentVariable implements EnvironmentVariableInterface
{
    private string $cast = '';

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
        $this->cast = 'bool';
        return $this;
    }


    public function asInt(): EnvironmentVariableInterface
    {
        $this->cast = 'int';
        return $this;
    }


    public function asFloat(): EnvironmentVariableInterface
    {
        $this->cast = 'float';
        return $this;
    }
}
