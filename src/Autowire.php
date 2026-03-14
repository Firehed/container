<?php

declare(strict_types=1);

namespace Firehed\Container;

use Closure;
use Generator;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Utilities for analyzing autowire compatibility.
 *
 * @internal
 */
class Autowire
{
    /**
     * Types that cannot meaningfully be autowired from a container.
     * These are internal PHP types that are not instantiable or are
     * created through special language constructs.
     */
    private const NON_AUTOWIRABLE_TYPES = [
        Closure::class,
        Generator::class,
    ];

    /**
     * Get the dependency type name for a required parameter.
     *
     * This validates that the parameter can be autowired and returns
     * the fully-qualified class/interface name to resolve.
     *
     * @param class-string $declaringClass The class containing this parameter (for error messages)
     * @return class-string The type to resolve from the container
     * @throws Exceptions\UntypedValue If the parameter cannot be autowired
     */
    public static function getRequiredDependencyType(
        ReflectionParameter $param,
        string $declaringClass,
    ): string {
        if (!$param->hasType()) {
            throw new Exceptions\UntypedValue($param->getName(), $declaringClass);
        }

        $type = $param->getType();

        // TODO: support ReflectionUnionType (#35), ReflectionIntersectionType (#36)?
        if (!$type instanceof ReflectionNamedType) {
            throw new Exceptions\UntypedValue($param->getName(), $declaringClass);
        }

        if ($type->isBuiltin()) {
            throw new Exceptions\UntypedValue($param->getName(), $declaringClass);
        }

        $typeName = $type->getName();

        if (in_array($typeName, self::NON_AUTOWIRABLE_TYPES, true)) {
            throw new Exceptions\UntypedValue($param->getName(), $declaringClass);
        }

        /** @var class-string */
        return $typeName;
    }

    /**
     * Check if a constructor parameter can be autowired.
     *
     * A parameter is autowirable if:
     * - It is optional (has a default value), OR
     * - It is typed with a non-builtin class/interface type
     */
    public static function isParameterAutowirable(ReflectionParameter $param): bool
    {
        if ($param->isOptional()) {
            return true;
        }

        if (!$param->hasType()) {
            return false;
        }

        $type = $param->getType();

        if (!$type instanceof ReflectionNamedType) {
            return false;
        }

        if ($type->isBuiltin()) {
            return false;
        }

        if (in_array($type->getName(), self::NON_AUTOWIRABLE_TYPES, true)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a class can be autowired based on its constructor signature.
     *
     * @param class-string $className
     */
    public static function isEligible(string $className): bool
    {
        $rc = new ReflectionClass($className);

        if (!$rc->isInstantiable()) {
            return false;
        }

        if (!$rc->hasMethod('__construct')) {
            return true;
        }

        $constructor = $rc->getMethod('__construct');

        if (!$constructor->isPublic()) {
            return false;
        }

        foreach ($constructor->getParameters() as $param) {
            if (!self::isParameterAutowirable($param)) {
                return false;
            }
        }

        return true;
    }
}
