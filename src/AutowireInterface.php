<?php
declare(strict_types=1);

namespace Firehed\Container;

interface AutowireInterface
{
    /** @return ?class-string */
    public function getWiredClass(): ?string;

    /**
     * @param class-string $class
     */
    public function withClass(string $class): self;
}
