# Container
A PSR-11 compliant Dependency Inversion Container

[![Test](https://github.com/Firehed/container/actions/workflows/test.yml/badge.svg)](https://github.com/Firehed/container/actions/workflows/test.yml)
[![Static analysis](https://github.com/Firehed/container/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/Firehed/container/actions/workflows/static-analysis.yml)
[![Lint](https://github.com/Firehed/container/actions/workflows/lint.yml/badge.svg)](https://github.com/Firehed/container/actions/workflows/lint.yml)
[![Packagist](https://img.shields.io/packagist/v/firehed/container.svg)](https://packagist.org/packages/firehed/container)

# Quick Start

If you're already familiar with Dependency Inversion, auto-wiring, and PSR-11, start here.

Install the library:
```
composer require psr/container firehed/container
```

Get a container:
```php
$container = \Firehed\Container\AutoDetect::instance('config');
```

> [!TIP]
> AutoDetect mode will cache the autowiring output except in a development environment.

Configure your app/services:

```
<?php
// config/someDefinitionFile.php

declare(strict_types=1);

use Firehed\Container\TypedContainerInterface as TC;

use function Firehed\Container\env;
use function Firehed\Container\factory;

return [
    // Env vars, with or without defaults
    'SOME_ENV' => env('SOME_ENV'), // Reads env at runtime, returns as string (throws if not set)
    'SOME_BOOL_ENV' => env('SOME_BOOL_ENV')->asBool(), // Env converted to boolean
    'OPTIONAL_INT_ENV' => env('OPTIONAL_INT_ENV', '42')->asInt(), // Env with default value, converted to int

    // Object types
    MyService::class, // Autowired from constructor
    MyInterface::class => MyImplementation::class, // Maps interface to implementation
    // Classes with scalar parameters need explicit wiring
    MyComplexService::class => function (TC $c) {
        return new MyComplexService(
            dep1: $c->get(MyInterface::class),
            dep2: $c->get(MyService::class),
            value1: $c->get('OPTIONAL_INT_ENV'),
        );
    },

    // factory() definitions will call the definition on each access.
    MyUniqueThing::class => factory(fn () => new MyUniqueThing()),
];
```

> [!IMPORTANT]
> While the container supports (and is designed around) class autowiring, all
> classes to be wired must still be listed in the config definition. This
> ensures that the cached container will not need to fall back to reflection at
> runtime in non-development environments.

All `.php` files in `config` will be included, _non-recursively_.
This allows you to organize your config/wiring according to your own needs and preferences.

Definitions are always evaluated lazily - nothing will be read/instantiated until `$container->get()` is called for it.

All values, except for those defined through `factory()`, will be memoized.
Functionally, this results in a singleton for object types managed by the container.

Want more control over config file inclusion?
See `Usage` below.

# Documentation and Detailed Examples

## Installation

```
composer require firehed/container
```

## Automatic Setup

If your software is following common conventions, the container bootstrapping can be greatly simplified:

```php
$container = \Firehed\Container\AutoDetect::instance('config');
```

It will auto-detect your environment, looking at `ENVIRONMENT` or `ENV` environment variables, in that order.
If it's any of `local`, `dev`, or `development` (case-insensitive), then it will use the dev container, which is not cached or compiled.
Any other value will run the compilation process, writing the output to `AutoDetect::$compiledOutputPath = 'vendor/compiledConfig.php'`.
You may change the output directory by changing that variable (be mindful of `getcwd()`!); the default writes into Composer's `vendor` directory since it's commonly `gitignore`d.

> [!NOTE]
> As the API implies, this returns a singleton instance of the conatiner.
>
> This works well for most modern applications, as well as for transitioning older applications using a `$config = require 'config.php';` approach.
> 
> If you need different behavior, `AutoDetect::from($configDirectory)` produces a new instance on each call.

## Manual Setup

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

$container = $builder->build();
return $container; // Or use inline, as you see fit.
```

## Definition API

All files added to the `BuilderInterface` must `return` an `array`.
The keys of the array will map to `$id`s that can be checked for existence with `has($id)`, and the values of the array will be returned when those keys are provided to `get($id)`.


> [!NOTE]
> The library output implements a `TypedContainerInterface`, which adds docblock generics readable by tools like PHPStan and Psalm to PSR-11.
> It assumes you are following the above convention; not doing so could result in misleading output.
> This has no effect at runtime, and only helps during the development and CI.

### Examples

The most concise examples are all part of the unit tests: [`tests/ValidDefinitions`](tests/ValidDefinitions).

### Class Autowiring

One of the primary mechnaics of this library is class autowiring.

Autowiring allows the container to determine the dependencies of a class (using reflection) and automatically provide configured values when accessed.
This drastically reduces the amount of config definition code, along with reduced config churn as definitions change over time.

_Any class that has zero or more typed, non-scalar constructor parameters can be autowired._

To have the container autowire a class, simply include its fully-qualified class name in the definition array:

```php
return [
    MyService::class,
];
```

> [!TIP]
> Prefer the [`::class` keyword](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.class) over class name strings.
> This reduces definition errors, especially when combined with linting, static analysis, and IDEs.

The following are all equivalent definitions:

```php
<?php
// Given classes like this:
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
```

```php
<?php
return [
    MySpecialClass::class,
    MyOtherClass::class,
];
// $container->get(MyOtherClass::class) returns an instance of MyOtherClass with
// MySpecialClass provided to its constructor.
```
```php
<?php
return [
    MySpecialClass::class => function () {
        return new MySpecialClass();
    },
    MyOtherClass::class => function (TypedContainerInterface $c) {
        return new MyOtherClass($c->get(MySpecialClass::class));
    },
];
```

The topmost example is recommended for configuring any class that can be autowired.


> [!WARNING]
> Dependency autowiring requires use of fully-qualified class names (FQCNs) as keys for object types (classes, interfaces, enums).
> 
> While you _may_ add additional aliases, you _must_ add defintions with FQCNs for autowiring to recognize that dependencies are available.
>
> e.g.
> ```php
> return [
>   Doctrine\ORM\EntityManager::class => fn ($c) => Doctrine\ORM\EntityManager::create(...),
>   // For autowiring the interface
>   Doctrine\ORM\EntityManagerInterface::class => Doctrine\ORM\EntityManager::class,
>   // Manual access, e.g. `$container->get('em')`.
>   'em' => Doctrine\ORM\EntityManager::class,
> ];

### Manual Wiring

If a class does not support autowiring or you'd prefer not to use it, provide a closure that returns a new implementation.

```php
<?php

use Firehed\Container\TypedContainerInterface;

return [
    'FILE_PATH' => env('FILE_PATH'),
    ClassUsingPath::class => function (TypedContainerInterface $c) {
        return new ClassUsingPath(path: $c->get('FILE_PATH'));
    },
];
```

> [!NOTE]
> You may omit the type hint, or the argument entirely if not needed.
> Short closures are also supported.

```php
<?php

return [
    ClassUsingPath::class => fn () => new ClassUsingPath(path: '/dev/null'),
];
``` 

### Aliases and mapping interfaces to implementations

Use the interface class name as the key, and the preferred class name as the value.

```php
<?php

return [
    MyClass::class, // $c->get(MyClass::class) returns MyClass
    MyInterface::class => MyClass::class, // $c->get(MyInterface::class) returns MyClass
    'c' => MyClass::class, // $c->get('c') returns MyClass
];
```

If you need to define an implementation conditionally (e.g. one thing in dev, a different one in prod):

```php
<?php

use function Firehed\Container\env;

return [
    'isDevMode' => env('DEV', '0')->asBool(),
    MyClass::class,
    MyMockClass::class,
    MyInterface::class => function ($c) {
        if ($c->get('isDevMode') {
            return $c->get(MyMockClass::class);
        }
        return $c->get(MyClass::class),
    },
];
```





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
- `asEnum`

These are roughly equivalent to e.g. `(int) getenv('SOME_ENV_VAR')`, with the exception that `asBool` will only allow values `0`, `1`, `"true"`, and `"false"` (case-insensitively).

`asEnum` takes a class-string to a **string-backed** enum that you have defined, and will use `::from($envValue)` to hydrate from the environment value.
This does not attempt to locally normalize values, so the envvar value MUST match the backing value exactly.

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
    'some_enum' => env('SOME_ENUM')->asEnum(MyEnum::class),

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
    'some_enum' => function () {
        $value = getenv('SOME_ENUM');
        if ($value === false) {
            throw new Firehed\Container\Exceptions\EnvironmentVariableNotSet('SOME_ENUM');
        }
        return MyEnum::from($value);
    },

    // Counterexample!
    'getenv' => 'whatever_value_is_in_your_current_environment',
];
```

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


