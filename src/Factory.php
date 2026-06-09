<?php

declare(strict_types=1);

namespace Firehed\Container;

use Closure;

class Factory implements ShorthandDefinitionInterface
{
    /** @var ?class-string */
    private ?string $classToAutowire = null;

    private Compiler\CodeGeneratorInterface $codeGenerator;

    public function __construct(private ?Closure $def)
    {
    }

    public function needsClass(): bool
    {
        return $this->def === null;
    }

    /** @param class-string $class */
    public function withClass(string $class): self
    {
        $new = clone $this;
        $new->classToAutowire = $class;
        return $new;
    }

    public function isCacheable(): bool
    {
        return false;
    }

    public function resolve(TypedContainerInterface $container, EnvReader $envReader): mixed
    {
        if ($this->def !== null) {
            $rebound = $this->def->bindTo(null);
            assert($rebound !== null);
            return $rebound($container);
        }

        assert($this->classToAutowire !== null, 'Class must be set for factory without definition');
        return Autowire::instantiate($this->classToAutowire, $container);
    }

    public function generateCode(): string
    {
        if ($this->def !== null) {
            $this->codeGenerator = new ClosureDefinition($this->def);
        } else {
            assert($this->classToAutowire !== null, 'Class must be set for factory without definition');
            $this->codeGenerator = new Compiler\AutowiredValue($this->classToAutowire);
        }
        return $this->codeGenerator->generateCode();
    }

    /** @return class-string[] */
    public function getDependencies(): array
    {
        return $this->codeGenerator->getDependencies();
    }
}
