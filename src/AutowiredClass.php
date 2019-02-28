<?php
declare(strict_types=1);

namespace Firehed\Container;

class AutowiredClass implements AutowireInterface
{
    /** @var ?string */
    private $class;

    public function __construct(?string $class = null)
    {
        $this->class = $class;
    }

    public function getWiredClass(): ?string
    {
        return $this->class;
    }
}
