<?php
declare(strict_types=1);

namespace Firehed\Container;

interface BuilderInterface
{
    /**
     * @param mixed[] $defs
     */
    public function addDefinitions(array $defs): void;

    public function addFile(string $file): void;

    public function build(): TypedContainerInterface;
}
