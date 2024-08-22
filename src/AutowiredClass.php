<?php
declare(strict_types=1);

namespace Firehed\Container;

class AutowiredClass implements AutowireInterface
{
    /** @param ?class-string $class */
    public function __construct(private ?string $class = null)
    {
    }

    /** @inheritdoc */
    public function getWiredClass(): ?string
    {
        return $this->class;
    }
}
