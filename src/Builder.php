<?php
declare(strict_types=1);

namespace Firehed\Container;

use Psr\Container\ContainerInterface;
use UnexpectedValueException;

class Builder implements BuilderInterface
{
    /** @var mixed[] */
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

    /**
     * @param mixed[] $defs
     * @return mixed[]
     */
    private function parseDefs(array $defs): array
    {
        $output = [];

        foreach ($defs as $key => $value) {
            if (is_int($key)) {
                // Remap extra-lazy autowiring
                $key = $value;
                $value = autowire();
            }
            // This assumes that any array key which is a FQCN for an interface
            // is an interface-to-implementation wiring. This means that simple
            // string value MUST NOT be keyed to an interface name
            if (interface_exists($key) && is_string($value)) {
                // This is a factory so that if the value being proxied is
                // a factory, the behavior passes through. If it isn't, the
                // downstream will still cache as expected
                $value = factory(function ($c) use ($value) {
                    return $c->get($value);
                });
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
