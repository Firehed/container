<?php
declare(strict_types=1);

namespace Firehed\Container;

use Psr\Container\ContainerInterface;

interface BuilderInterface
{
    public function addFile(string $file): void;

    public function build(): ContainerInterface;
}
