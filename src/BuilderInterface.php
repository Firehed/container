<?php
declare(strict_types=1);

namespace Firehed\Container;

interface BuilderInterface
{
    /**
     * Imports the provided definitions into the container builder. The
     * container returned by `build()` will have all of these defintions.
     *
     * Values with a with no key (or any integer key, though doing so
     * explicitly is NOT RECOMMENDED) will be treated as an autowirable class.
     * That is:
     *
     * `[Foo::class]`
     *
     * will be interpreted as `[Foo::class => autowire(Foo::class)]`
     *
     * Explicit string keys will be used exactly as provided and follow all
     * other value-resolving semantics in the Definition API documentation
     * (autowiring, environment variables, typecasting, etc).
     *
     * @param mixed[] $defs
     */
    public function addDefinitions(array $defs): void;

    /**
     * Take the contents of a PHP file which returns an array of definitions
     * and import them into the container.
     *
     * The returned array must be of the same format used by `addDefinitions()`.
     */
    public function addFile(string $file): void;

    public function build(): TypedContainerInterface;
}
