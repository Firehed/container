<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

trait NoDependenciesTrait
{
    public function getDependencies(): array
    {
        return [];
    }
}
