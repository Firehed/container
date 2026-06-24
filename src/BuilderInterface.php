<?php

declare(strict_types=1);

namespace Firehed\Container;

interface BuilderInterface
{
    public function addDirectory(string $directory): void;

    public function addFile(string $file): void;

    public function build(): TypedContainerInterface;
}
