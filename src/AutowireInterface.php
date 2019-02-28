<?php
declare(strict_types=1);

namespace Firehed\Container;

interface AutowireInterface
{
    public function getWiredClass(): ?string;
}
