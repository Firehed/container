<?php
declare(strict_types=1);

namespace Firehed\Container;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use UnexpectedValueException;

class Builder implements BuilderInterface
{
    /** @var mixed[] */
    private $defs = [];

    /** @var ContainerExceptionInterface[] */
    private $errors = [];

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
                assert(is_string($value), 'Values without keys must be strings that correspond to autowirable classes');
                $key = $value;
                $value = autowire();
            }
            // This assumes that any array key which is a FQCN for an interface
            // is an interface-to-implementation wiring. This means that simple
            // string value MUST NOT be keyed to an interface name
            if (interface_exists($key) && is_string($value)) {
                if (!class_exists($value)) {
                    $this->errors[] = new Exceptions\InvalidClassMapping($key, $value);
                    continue;
                }
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
        if ($this->errors !== []) {
            throw $this->errors[0];
        }
        $container = new DevContainer($this->defs);

        return $container;
    }
}
