<?php
declare(strict_types=1);

namespace Firehed\Container;

class AutowiredClass implements AutowireInterface
{
    /** @var ?class-string */
    private $class;

    /** @param ?class-string $class */
    public function __construct(?string $class = null)
    {
        $this->class = $class;
    }

    /** @inheritdoc */
    public function getWiredClass(): ?string
    {
        return $this->class;
    }
}
