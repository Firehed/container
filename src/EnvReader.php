<?php

declare(strict_types=1);

namespace Firehed\Container;

readonly class EnvReader
{
    /**
     * @var array<string, string>
     */
    private array $getenv;

    /**
     * @param array<string, string> $env Typically $_ENV
     */
    public function __construct(private array $env)
    {
        $this->getenv = getenv();
    }

    public function read(string $key): ?string
    {
        if (array_key_exists($key, $this->env)) {
            // check non string
            return $this->env[$key];
        }
        // if (array_key_exists($key, $this->getenv)) {
        return $this->getenv[$key] ?? null;
    }

    // todo: debuginfo
}
