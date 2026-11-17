<?php

namespace Firehed\Container;

use Closure;

/** @param ?class-string $class */
function autowire(?string $class = null): ShorthandDefinitionInterface
{
    return new AutowiredClass($class);
}

// env() and fileOrEnv() forward only the arguments actually passed, so that
// an explicit null default stays distinct from no default.

function env(string $name, ?string $default = null): EnvironmentVariableInterface
{
    return new EnvironmentVariable(new EnvSource($name), ...(func_num_args() === 2 ? [$default] : []));
}

function factory(?Closure $def = null): ShorthandDefinitionInterface
{
    return new Factory($def);
}

function fileContents(string $path): EnvironmentVariableInterface
{
    return new EnvironmentVariable(new FileSource($path));
}

function fileOrEnv(string $name, ?string $default = null): EnvironmentVariableInterface
{
    return new EnvironmentVariable(new FileOrEnvSource($name), ...(func_num_args() === 2 ? [$default] : []));
}
