<?php

declare(strict_types=1);

namespace Firehed\Container;

interface BuilderInterface
{
    /**
     * Add all `.php` files in the directory to the builder. Not recursive.
     */
    public function addDirectory(string $directory): void;

    public function addFile(string $file): void;

    public function build(): TypedContainerInterface;
}
