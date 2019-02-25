<?php
declare(strict_types=1);

namespace Firehed\Container;

class EnvironmentVariable implements EnvironmentVariableInterface
{
    /** @var string */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
    public function getName(): string
    {
        return $this->name;
    }
}
