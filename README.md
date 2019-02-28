# Container
A PSR-11 compliant Dependency Inversion Container

## Why another container implementation?

The primary motivation for creating this was to have a container implementation
that's optimized for containeried deployment in a long-running process (like
ReactPHP and PHP-PM)

The usage and API is is highly inspired by PHP-DI, but adds functionality to
support factories at definition-time (rather than exclusively at access-time
with `make`). This is intended to reduce unpredictable behavior of services
in concurrent environments while strictly adhering to the PSR container
specification.

### Differences from PHP-DI

- Only `get()` and `has()` methods exist on the container

- The `factory` function has _completely_ different behavior:
  the closure it wraps will be called every time it is requested through `get` (PHP-DI exposes this as `$container->make()`).
  In PHP-DI, `factory` is just alternate syntax for defining a service through a closure.

- When an interface is mapped to an implementation, the default behavior is to return the configured implementation.
  In PHP-DI, `SomeInterface::class => autowire(SomeImplementation::class)` does NOT point to an explcitly-configured `SomeImplementation`

- A shorthand syntax for interface-to-implementation has been added

- Implicit autowiring of classes is not supported.
  This is intentional in order to maximize compiler optimizations.

- When recursively resolving dependencies, all required parameters must also be explicitly configured (see above)

- Annotations are not supported.
  In future versions, `@param` annotations _may_ be supported; `@Inject` will never be.


## Installation

```
composer require firehed/container
```

## Usage

### `autowire(?string $classToAutowire = null)`
Using `autowire` will use reflection to attempt to determine the specified class's dependencies, recursively resolve them, and return a shared instance of that object.

Required parameters **must** have a typehint in order to be resolved.
That typehint may be to either a class or an interface; in both cases, that dependency must also be defined (but can also be autowired).
Required parameters with value types (scalars, arrays, etc) are not supported and must be manually wired.

Optional parameters will always have their default value provided.

### factory

### `env(string $variableName, ?string $default = null)`
Use `env` to embed environment variables in your container. Like other non-
factory values, these will be cached for the lifetime of the script.

**IMPORTANT**: Do not use `getenv` or `$_ENV` to access environment variables!
If you do so, compiled containers will get the *compile-time* value set, which
is almost certainly not the behavior you want. Instead, use the `env` wrapper,
which will defer the access of the environment variable until the first time it
is used.

If *and only if* you want a value compiled in, you must use `getenv` directly.

Source definitions like this:
```php
use function Firehed\Container\env;
return [
    'some_key' => env('SOME_ENV_VAR'),
    'some_key_with_default' => env('SOME_ENV_VAR', 'default_value'),
    'some_key_with_null_default' => env('SOME_ENV_VAR', null),

    // Counterexample!
    'getenv' => getenv('VALUE_AT_COMPILE_TIME'),
];
```

will compile to code similar to this:
```php
return [
    'some_key' => function () {
        $value = getenv('SOME_ENV_VAR');
        if ($value === false) {
            throw new Firehed\Container\Exceptions\EnvironmentVariableNotSet('SOME_ENV_VAR');
        }
        return $value;
    },
    'some_key_with_default' => function () {
        $value = getenv('SOME_ENV_VAR');
        if ($value === false) {
            return 'default_value';
        }
        return $value;
    },
    'some_key_with_null_default' => function () {
        $value = getenv('SOME_ENV_VAR');
        if ($value === false) {
            return null;
        }
        return $value;
    },

    // Counterexample!
    'getenv' => 'whatever_value_is_in_your_current_environment',
];
```
