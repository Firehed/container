# Container
A PSR-11 compliant Dependency Inversion Container

## Why another container implementation?

The primary motivation for creating this was to have a container implementation that's optimized for containerized deployment in a long-running process (like ReactPHP and PHP-PM).

The usage and API is is highly inspired by PHP-DI, but adds functionality to support factories at definition-time (rather than exclusively at access-time with `make`).
This is intended to reduce unpredictable behavior of services in concurrent environments while strictly adhering to the PSR container specification.

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

The primary interface to the container is through the `BuilderInterface`.

There are two implementations:

### `Builder`
The `Builder` class will create a dev container, which will determine dependencies on the fly.
This is intended for use during development - reflection for autowired classes is performed on every request, which is convenient while changes are being made but adds overhead.

### `Compiler`
The `Compiler` class will generate optimized code and write it to a file once, load that file, and return a container that uses the optimized code.
Reflection for autowiring is only performed at compile-time, so this will run significantly faster than the dev container.
However, whenever any definition changes (including constructor signatures of autowired classes), the file must be recompiled.

It is **highly recommended** to a) use the `Compiler` implementation in non-dev environments, and b) compile the container during your build process.

#### Running the compiler

The compilation process runs automatically the first time `build()` is called.

### Example
```php
<?php
declare(strict_types=1);

// Include Composer's autoloader if not already done
require 'vendor/autoload.php';

// If using a tool like dotenv, apply it here
/*
if (file_exists(__DIR__.'/.env')) {
    Dotenv\Dotenv::create(__DIR__)->load();
}
 */

$isDevMode = getenv('ENVIRONMENT') === 'development';

if ($isDevMode) {
    $builder = new Firehed\Container\Builder();
} else {
    $builder = new Firehed\Container\Compiler();
}

// Each definition file must return a definition array (see below)
foreach (glob('config/*.php') as $definitionFile) {
    $builder->addFile($definitionFile);
}

return $builder->build();
```

## Definition API

All files added to the `BuilderInterface` must `return` an `array`.
The keys of the array will map to `$id`s that can be checked for existence with `has($id)`, and the values of the array will be returned when those keys are provided to `get($id)`.

It is **highly recommended** that class instances use their fully-qualified class name as an array key, and to additionally create a separate interface-to-implementation mapping.
The latter will happen automatically when a key is the fully-qualified name of an `interface` and the value is a string that maps to a class name.

### Simple values

If a scalar or array is provided as a value, that value will be returned unmodified.

**Exception**: if the value is a `string` AND the key is the name of a declared `interface`, it will automatically be treated as an interface-to-implementation mapping and processed as `InterfaceName::class => autowire(ImplementationName::class)`

### Closures

If a closure is provided as a value, that closure will be executed when `get()` is called and the value it returns will be returned.
The container will be provided as the first and only parameter to the closure, so definitions may depend on other services.
For services that do not have dependencies, the closure may be defined as a function that takes no parameters (a "thunk"), though at execution time the container will still be passed in (and subsequently ignored).
Do not use the `use()` syntax to access other container definitions.

Since computed values are cached (except when wrapped with `factory`, see below), the closure will only be executed once regardless of how many times `get()` is called.

Any definition that should return an instance of an object **must** be defined by a closure, or using one of the helpers described below.
Directly instantiating the class in the definition file is invalid.

```php
<?php
use Psr\Container\ContainerInterface;
return [
    // This will provide a single connection to your database, deferring the
    // connection until either directly accessed or a service with PDO as a
    // dependency is accessed.
    // Note: you may opt to elide the `ContainerInterface` typehint for brevity
	PDO::class => function (ContainerInterface $c) {
	    // This example assumes pdo_dsn, database_user, and database_pass are
        // defined elsewhere (probably using the `env` helper)
	    return new PDO(
	        $c->get('pdo_dsn'),
	        $c->get('database_user'),
	        $c->get('database_pass')
	    );
	},
];
```

### `autowire(?string $classToAutowire = null)`
Using `autowire` will use reflection to attempt to determine the specified class's dependencies, recursively resolve them, and return a shared instance of that object.

Required parameters **must** have a typehint in order to be resolved.
That typehint may be to either a class or an interface; in both cases, that dependency must also be defined (but can also be autowired).
Required parameters with value types (scalars, arrays, etc) are not supported and must be manually wired.

Optional parameters will always have their default value provided.

#### Automatic autowiring
In the returned definition array, having a bare string value with no key will treat the value as a key to be autowired.

The following are all equivalent definitions:

```php
<?php
return [
    MySpecialClass::class,
];
```
```php
<?php
use function Firehed\Container\autowire;
return [
    MySpecialClass::class => autowire(),
];
```
```php
<?php
use function Firehed\Container\autowire;
return [
    MySpecialClass::class => autowire(MySpecialClass::class),
];
```
```php
<?php
return [
    MySpecialClass::class => function () {
        return new MySpecialClass();
    },
];
```
### `factory(?closure $body = null)`
Use `factory` to return a new copy of the class or value every time it is accessed through `get()`

If a paramater is not provided to the definition, the key will be used to autowire a definition.
If a closure is provided, that closure will be executed instead.

### `env(string $variableName, ?string $default = null)`
Use `env` to embed environment variables in your container.
Like other non-factory values, these will be cached for the lifetime of the script.

**IMPORTANT**: Do not use `getenv` or `$_ENV` to access environment variables!
If you do so, compiled containers will get the *compile-time* value set, which is almost certainly not the behavior you want.
Instead, use the `env` wrapper, which will defer the access of the environment variable until the first time it is used.

If *and only if* you want a value compiled in, you must use `getenv` directly.

Source definitions like this:
```php
<?php
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
<?php
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