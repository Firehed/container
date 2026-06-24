<?php

declare(strict_types=1);

namespace Firehed\Container;

interface BuilderInterface
{
    /**
     * Add all `.php` files in the directory to the builder. Not recursive.
     *
     * @param non-empty-literal-string $directory The path to include from. This
     * may be relative or absolute but should be a literal value. Setting this
     * from a dynamic source is NOT RECOMMENDED, and could pose a security risk
     * if user-controlled.
     */
    public function addDirectory(string $directory): void;

    public function addFile(string $file): void;

    public function build(): TypedContainerInterface;
}
