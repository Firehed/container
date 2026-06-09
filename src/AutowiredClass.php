<?php

declare(strict_types=1);

namespace Firehed\Container;

class AutowiredClass implements ShorthandDefinitionInterface
{
    private Compiler\AutowiredValue $codeGenerator;

    /** @param ?class-string $class */
    public function __construct(private ?string $class = null)
    {
    }

    public function needsClass(): bool
    {
        return $this->class === null;
    }

    /**
     * @param class-string $class
     */
    public function withClass(string $class): self
    {
        return new self($class);
    }

    public function isCacheable(): bool
    {
        return true;
    }

    public function resolve(TypedContainerInterface $container, EnvReader $envReader): mixed
    {
        assert($this->class !== null, 'Class must be set before resolving');
        return Autowire::instantiate($this->class, $container);
    }

    public function generateCode(): string
    {
        assert($this->class !== null, 'Class must be set before generating code');
        $this->codeGenerator = new Compiler\AutowiredValue($this->class);
        return $this->codeGenerator->generateCode();
    }

    /** @return class-string[] */
    public function getDependencies(): array
    {
        return $this->codeGenerator->getDependencies();
    }
}
