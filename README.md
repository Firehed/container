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

## Installation

```
composer require firehed/container
```

## Usage

### autowire

### factory

### env
Use `env` to embed environment variables in your container. Like other non-
factory values, these will be cached for the lifetime of the script.

**IMPORTANT**: Do not use `getenv` or `$_ENV` to access environment variables!
If you do so, compiled containers will get the *compile-time* value set, which
is almost certainly not the behavior you want. Instead, use the `env` wrapper,
which will defer the access of the environment variable until the first time it
is used.

If *and only if* you want a value compiled in, you must use `getenv` directly.

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
```php
use function Firehed\Container\env;
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
