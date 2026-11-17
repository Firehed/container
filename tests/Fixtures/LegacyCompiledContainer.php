<?php

declare(strict_types=1);

namespace Firehed\Container\Fixtures;

use Firehed\Container\CompiledContainer;

/**
 * Mirrors output from versions that cast inside the generated methods and
 * have no $casts map.
 */
class LegacyCompiledContainer extends CompiledContainer
{
    protected array $factories = [];
    protected array $mappings = [
        'bool' => 'resolveBool',
        'enum' => 'resolveEnum',
        'int' => 'resolveInt',
    ];

    protected function resolveBool(): bool
    {
        $value = $this->envReader->read('CONTAINER_UNITTEST_NOT_SET');
        if ($value === null) {
            $value = 'true';
        }
        return \Firehed\Container\EnvironmentVariable::parseBool($value);
    }

    protected function resolveEnum(): Environment
    {
        $value = $this->envReader->read('CONTAINER_UNITTEST_NOT_SET');
        if ($value === null) {
            $value = 'testing';
        }
        return Environment::from($value);
    }

    protected function resolveInt(): int
    {
        $value = $this->envReader->read('CONTAINER_UNITTEST_NOT_SET');
        if ($value === null) {
            $value = '42';
        }
        return (int)$value;
    }
}
