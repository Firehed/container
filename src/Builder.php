<?php
declare(strict_types=1);

namespace Firehed\Container;

use Psr\Container\ContainerInterface;

class Builder
{
    private $defs = [];

    public function addFile(string $path): void
    {
        $defs = require $path;
        foreach ($defs as &$key => $def) {
            if (is_int($key)) {
                $key = $def;
            }
        }
        $this->defs = array_merge($defs, $this->defs);
    }

    public function build(): ContainerInterface
    {
        return new Container($this->buildDefsForContainer());
    }

    private function buildDefsForContainer(): array
    {
    }
}
