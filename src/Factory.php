<?php

declare(strict_types=1);

namespace Firehed\Container;

use Closure;

class Factory implements FactoryInterface, DefinitionInterface
{
    /** @var ?class-string */
    private ?string $classToAutowire = null;

    private Compiler\CodeGeneratorInterface $codeGenerator;

    public function __construct(private ?Closure $def)
    {
    }

    public function hasDefinition(): bool
    {
        return $this->def !== null;
    }

    public function getDefinition(): Closure
    {
        assert($this->def !== null);
        return $this->def;
    }

    /**
     * Sets the class to autowire when there's no closure definition.
     * Used by builders when factory() is called without arguments.
     *
     * @param class-string $class
     */
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
            $this->codeGenerator = new Compiler\ClosureValue($this->def);
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
