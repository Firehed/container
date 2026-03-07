<?php

declare(strict_types=1);

namespace Firehed\Container;

class EnvReader
{
    /**
     * @var array<string, string>
     */
    private readonly array $getenv;

    /**
     * @param mixed[] $env Typically $_ENV
     */
    public function __construct(private readonly array $env)
    {
        $this->getenv = getenv();
    }

    public function read(string $key): ?string
    {
        if (array_key_exists($key, $this->env)) {
            $value = $this->env[$key];
            if (!is_string($value)) {
                throw new \TypeError('$_ENV contained a non-string value for key ' . $key);
            }
            return $value;
        }
        // if (array_key_exists($key, $this->getenv)) {
        return $this->getenv[$key] ?? null;
    }

    // todo: debuginfo
}
