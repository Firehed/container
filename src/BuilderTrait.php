<?php

declare(strict_types=1);

namespace Firehed\Container;

use Closure;

trait BuilderTrait
{
    /**
     * Given the contents of a standard container config file, pre-process some
     * of the shorthand/"magical" definitions into actual DefinitionInterface
     * objects.
     *
     * @param mixed[] $definitions
     * @return iterable<string, DefinitionInterface>
     */
    private function processDefinitions(array $definitions)
    {
        foreach ($definitions as $key => $value) {
            // SomeClass::class (implicit autowiring; no key set)
            if (is_int($key)) {
                assert(is_string($value), 'Values without keys must be strings that correspond to autowirable classes');
                $key = $value;
                if (!class_exists($key)) {
                    $this->errors[] = new Exceptions\AmbiguousMapping($key);
                    continue;
                }
                $value = autowire($key);

                yield $key => $value;
                continue;
            }

            // SomeClass::class => utilityFunction()
            if ($value instanceof ShorthandDefinitionInterface && $value->needsClass()) {
                if (!class_exists($key)) {
                    $this->errors[] = new Exceptions\AmbiguousMapping($key);
                    continue;
                }
                $value = $value->withClass($key);

                yield $key => $value;
                continue;
            }

            // SomeClass::class => function (TypedContainerInterface $c) {
            //     return new SomeClass(...);
            // }
            if ($value instanceof Closure) {
                $value = new ClosureDefinition($value);

                yield $key => $value;
                continue;
            }

            // SomeInterface::class => SomeClassImplementingInterface::class
            //
            // This assumes that any array key which is a FQCN for an interface
            // is an interface-to-implementation wiring. This means that simple
            // string value MUST NOT be keyed to an interface name
            if (interface_exists($key) && is_string($value)) {
                if (!class_exists($value)) {
                    $this->errors[] = new Exceptions\InvalidClassMapping($key, $value);
                    continue;
                }

                yield $key => new AliasDefinition($value);
                continue;
            }

            // At this point, the only non-wrapped types should be scalars (
            // them.
            if (!$value instanceof DefinitionInterface) {
                $value = new ScalarDefinition($value);
            }

            // Finally, yield the normalized/encapsulated value
            yield $key => $value;
        }
    }
}
