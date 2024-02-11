# Container
A PSR-11 compliant Dependency Inversion Container

[![Build Status](https://github.com/Firehed/container/workflows/Test/badge.svg?branch=master)](https://github.com/Firehed/container/actions?query=workflow%3ATest+branch%3Amaster)
[![Packagist](https://img.shields.io/packagist/v/firehed/container.svg)](https://packagist.org/packages/firehed/container)

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

- When recursively resolving dependencies, all required parameters must also be explicitly configured (see above)

- Annotations are not supported.
  In future versions, `@param` annotations _may_ be supported; `@Inject` will never be.

### Design Opinions

Like many autowiring DI containers, this has some opinionated design decisions.
You may or may not agree, but it's important to document them to help you make an informed choice about whether this library is right for you.

- This is based around having a distinct build/compile stage for your application's deployment process.
  Implicit autowiring will NOT occur in the production-ready compiled container, which yields performance improvements.
  Add the autowired class name to any definition file to explicitly wire it (`Foo::class,` is sufficient, see "Automatic autowiring" below).

- Any `$id` that's a valid class string should return an instance of that class (or interface, enum).
  As of 0.6, this is reflected in the provided type information: `->get($id)` has Generic information for PHPStan where if a `class-string` is detected, get returns that class.
  This is not (currently) enforced at runtime, but be warned that e.g. `$container->get(LoggerInterface::class);` will be indicated as being a `LoggerInteface` to static analysis tools, and if the definition doesn't do that, you may get conflicts.

- All files should always be included.
  Do NOT skip files based on the environment.
  Instead, definitions should be conditional.
  Example:
  ```php
  <?php

  return [
      MyInterface::class => function ($c) {
          return $c->get('use_mocks')
              ? $c->get(MyImplementationMock::class)
              : $c->get(MyImplementationReal::class);
      },
  ];
  ```

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

> [!TIP]
> It is **highly recommended** to a) use the `Compiler` implementation in non-dev environments, and b) compile the container during your build process.

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

> [!TIP]
> If you're following the pattern above (config files in one directory), the `AutoDetect` class can do this for you.
> See [Auto-detection](#auto-detection), below.

## Definition API

All files added to the `BuilderInterface` must `return` an `array`.
The keys of the array will map to `$id`s that can be checked for existence with `has($id)`, and the values of the array will be returned when those keys are provided to `get($id)`.

It is **highly recommended** that class instances use their fully-qualified class name as an array key, and to additionally create a separate interface-to-implementation mapping.
The latter will happen automatically when a key is the fully-qualified name of an `interface` and the value is a string that maps to a class name.

> [!NOTE]
> The library output implements a `TypedContainerInterface`, which adds docblock generics readable by tools like PHPStan and Psalm to PSR-11.
> It assumes you are following the above convention; not doing so could result in misleading output.
> This has no effect at runtime, and only helps during the development and CI.

### Examples

The most concise examples are all part of the unit tests: [`tests/ValidDefinitions`](tests/ValidDefinitions).

### Simple values

If a scalar, array, or Enum is provided as a value, that value will be returned unmodified.

**Exception**: if the value is a `string` AND the key is the name of a declared `interface`, it will automatically be treated as an interface-to-implementation mapping and processed as `InterfaceName::class => autowire(ImplementationName::class)`
When doing so, you **SHOULD** write the mapping with a `::class` literal; e.g. `\Psr\Log\LoggerInterface::class => SomeLoggerImplementation::class`.
This approach (as compared to strings) not only provides additional clarity when reading the file, but allows static analysis tools to detect some errors.

Objects **may not** be directly provided as a value and **must** be provided as a closure; see below.
This is because the compiler cannot create an actual object instance, and thus would only work in development mode.

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

> [!IMPORTANT]
> Classes with any untyped constructor parameters, or those typed with `int/float/bool/array`, **cannot** be autowired.

#### Automatic autowiring
In the returned definition array, having a bare string value with no key will treat the value as a key to be autowired.

The following are all equivalent definitions:

```php
<?php
/**
class MySpecialClass
{
}
class MyOtherClass
{
    public function __construct(MySpecialClass $required)
    {
        // ...
    }
}
*/
return [
    MySpecialClass::class,
    MyOtherClass::class,
];
```
```php
<?php
use function Firehed\Container\autowire;
return [
    MySpecialClass::class => autowire(),
    MyOtherClass::class => autowire(),
];
```
```php
<?php
use function Firehed\Container\autowire;
return [
    MySpecialClass::class => autowire(MySpecialClass::class),
    MyOtherClass::class => autowire(MyOtherClass::class),
];
```
```php
<?php
return [
    MySpecialClass::class => function () {
        return new MySpecialClass();
    },
    MyOtherClass::class => function (ContainerInterface $c) {
        return new MyOtherClass($c->get(MySpecialClass::class));
    },
];
```

The topmost example is recommended for configuring any class that can be autowired.

### `factory(?closure $body = null)`
Use `factory` to return a new copy of the class or value every time it is accessed through `get()`

If a paramater is not provided to the definition, the key will be used to autowire a definition.
If a closure is provided, that closure will be executed instead.

### `env(string $variableName, ?string $default = null)`
Use `env` to embed environment variables in your container.
Like other non-factory values, these will be cached for the lifetime of the script.

`env` embeds a tiny DSL, allowing you to get the values set in the environment as an int, float, or bool rather than the native string read from the environment.
To use this, the following methods exist:

- `asBool`
- `asInt`
- `asFloat`

These are roughly equivalent to e.g. `(int) getenv('SOME_ENV_VAR')`, with the exception that `asBool` will only allow values `0`, `1`, `"true"`, and `"false"` (case-insensitively).

> [!WARNING]
> Do not use `getenv` or `$_ENV` to access environment variables!
> If you do so, compiled containers will get the *compile-time* value set, which is almost certainly not the behavior you want.
> Instead, use the `env` wrapper, which will defer the access of the environment variable until the first time it is used.
>
> If *and only if* you want a value compiled in, you must use `getenv` directly.

Source definitions like this:
```php
<?php
use function Firehed\Container\env;
return [
    'some_key' => env('SOME_ENV_VAR'),
    'some_key_with_default' => env('SOME_ENV_VAR', 'default_value'),
    'some_key_with_null_default' => env('SOME_ENV_VAR', null),

    'some_bool' => env('SOME_BOOL')->asBool(),
    'some_int' => env('SOME_INT')->asInt(),
    'some_float' => env('SOME_FLOAT')->asFloat(),

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

    'some_bool' => function () {
        $value = getenv('SOME_ENV_VAR');
        if ($value === false) {
            throw new Firehed\Container\Exceptions\EnvironmentVariableNotSet('SOME_ENV_VAR');
        }
        $value = strtolower($value);
        if ($value === '1' || $value === 'true') {
            return true;
        } elseif ($value === '0' || $value === 'false') {
            return false;
        } else {
            throw new OutOfBoundsException('Invalid boolean value');
        }
    },
    'some_int' => function () {
        $value = getenv('SOME_INT');
        if ($value === false) {
            throw new Firehed\Container\Exceptions\EnvironmentVariableNotSet('SOME_INT');
        }
        return (int)$value;
    },
    'some_float' => function () {
        $value = getenv('SOME_FLOAT');
        if ($value === false) {
            throw new Firehed\Container\Exceptions\EnvironmentVariableNotSet('SOME_FLOAT');
        }
        return (float)$value;
    },

    // Counterexample!
    'getenv' => 'whatever_value_is_in_your_current_environment',
];
```

## Auto-detection

If your software is following common conventions, the container bootstrapping can be greatly simplified:

```php
$container = \Firehed\Container\AutoDetect::from('config');
```

It will auto-detect your environment, looking at `ENVIRONMENT` or `ENV` environment variables, in that order.
If it's any of `local`, `dev`, or `development` (case-insensitive), then it will use the dev container, which is not cached or compiled.
Any other value will run the compilation process, writing the output to `AutoDetect::$compiledOutputPath = 'vendor/compiledConfig.php'`.
You may change the output directory by changing that variable (be mindful of `getcwd()`!); the default writes into Composer's `vendor` directory since it's commonly `gitignore`d.
