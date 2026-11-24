<?php

declare(strict_types=1);

namespace Firehed\Container;

use Closure;

trait GeneratedCodeTestTrait
{
    /**
     * Runs a definition's generated code the way a compiled container would.
     */
    private function evaluateGeneratedCode(string $code, EnvReader $envReader): mixed
    {
        $container = new class ($envReader) extends CompiledContainer {
        };
        $run = Closure::bind(function () use ($code): mixed {
            return eval($code);
        }, $container, CompiledContainer::class);
        return $run();
    }
}
