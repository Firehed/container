<?php

declare(strict_types=1);

namespace Firehed\Container;

use Closure;

class ClosureDefinition implements DefinitionInterface
{
    private Compiler\ClosureValue $codeGenerator;

    public function __construct(private Closure $closure)
    {
    }

    public function isCacheable(): bool
    {
        return true;
    }

    public function resolve(TypedContainerInterface $container, EnvReader $envReader): mixed
    {
        $rebound = $this->closure->bindTo(null);
        assert($rebound !== null);
        return $rebound($container);
    }

    public function generateCode(): string
    {
        $this->codeGenerator = new Compiler\ClosureValue($this->closure);
        return $this->codeGenerator->generateCode();
    }

    /** @return class-string[] */
    public function getDependencies(): array
    {
        return $this->codeGenerator->getDependencies();
    }
}
