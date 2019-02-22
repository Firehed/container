<?php
declare(strict_types=1);

namespace Firehed\Container;

use Psr\Container\ContainerInterface;
use UnexpectedValueException;

class Builder
{
    private $defs = [];

    public function addFile(string $path): void
    {
        $defs = require $path;
        if (!is_array($defs)) {
            throw new UnexpectedValueException(sprintf(
                'File %s did not return an array',
                $path
            ));
        }
        $this->defs = array_merge($this->parseDefs($defs), $this->defs);
    }

    private function parseDefs(array $defs)
    {
        $output = [];

        foreach ($defs as $key => $value) {
            // bare array elements will be treated as an autowired class
            // if (is_int($key)) {
            //     $key = $value;
            // }

            // This assumes that any array key which is a FQCN for an interface
            // is an interface-to-implementation wiring
            if (interface_exists($key)) {
                $value = function ($c) use ($value) {
                    return $c->get($value);
                };
            }

            $output[$key] = $value;
        }

        return $output;
    }

    public function build(): ContainerInterface
    {
        $container = new DevContainer($this->defs);

        return $container;
    }
}
