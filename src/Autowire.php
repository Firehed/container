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
     * Instantiate a class by resolving its constructor dependencies from the container.
     *
     * @throws Exceptions\AmbiguousMapping if the class does not exist
     */
    public static function instantiate(string $class, TypedContainerInterface $container): object
    {
        if (!class_exists($class)) {
            throw new Exceptions\AmbiguousMapping($class);
        }
        $rc = new ReflectionClass($class);

        if (!$rc->hasMethod('__construct')) {
            return new $class();
        }

        $construct = $rc->getMethod('__construct');
        $params = $construct->getParameters();
        $args = [];

        foreach ($params as $param) {
            if ($param->isOptional()) {
                $typeName = self::getOptionalDependencyType($param);
                if ($typeName !== null && $container->has($typeName)) {
                    $args[] = $container->get($typeName);
                } else {
                    $args[] = $param->getDefaultValue();
                }
            } else {
                $name = self::getRequiredDependencyType($param, $class);
                if (!$container->has($name)) {
                    throw Exceptions\NotFound::autowireMissing($name, $class, $param->getName());
                }
                $args[] = $container->get($name);
            }
        }

        return new $class(...$args);
    }

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
     * Extract the resolvable type name from a parameter, if any.
     *
     * @return ?class-string The type name if resolvable, null otherwise
     */
    private static function getResolvableTypeName(ReflectionParameter $param): ?string
    {
        if (!$param->hasType()) {
            return null;
        }

        $type = $param->getType();

        // TODO: support ReflectionUnionType (#35), ReflectionIntersectionType (#36)?
        if (!$type instanceof ReflectionNamedType) {
            return null;
        }

        if ($type->isBuiltin()) {
            return null;
        }

        $typeName = $type->getName();

        if (in_array($typeName, self::NON_AUTOWIRABLE_TYPES, true)) {
            return null;
        }

        /** @var class-string */
        return $typeName;
    }

    /**
     * Get the dependency type name for an optional parameter, if resolvable.
     *
     * Returns the type name if the parameter has an autowirable object type,
     * null otherwise (scalar, untyped, union, etc).
     *
     * @return ?class-string
     */
    public static function getOptionalDependencyType(ReflectionParameter $param): ?string
    {
        return self::getResolvableTypeName($param);
    }

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
        $typeName = self::getResolvableTypeName($param);
        if ($typeName === null) {
            throw new Exceptions\UntypedValue($param->getName(), $declaringClass);
        }
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
        return $param->isOptional() || self::getResolvableTypeName($param) !== null;
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

        // No constructor = eligible
        if (!$rc->hasMethod('__construct')) {
            return true;
        }

        // Non-public constructors are already excluded by isInstantiable() above
        foreach ($rc->getMethod('__construct')->getParameters() as $param) {
            if (!self::isParameterAutowirable($param)) {
                return false;
            }
        }

        return true;
    }
}
