<?php

declare(strict_types = 1);

// phpcs:ignoreFile

// Polyfills for Enum tooling

if (!function_exists('enum_exists')) {
    assert(version_compare(PHP_VERSION, '8.1.0', '<'));
    function enum_exists(string $enum, bool $autoload = true): bool
    {
        return false;
    }
}

if (!interface_exists('UnitEnum')) {
    assert(version_compare(PHP_VERSION, '8.1.0', '<'));
    interface UnitEnum
    {
        /** @return static[] */
        public static function cases(): array;
    }
}

if (!interface_exists('BackedEnum')) {
    assert(version_compare(PHP_VERSION, '8.1.0', '<'));
    interface BackedEnum extends UnitEnum
    {
        /** @param int|string $scalar */
        public static function from($scalar): static;
        /** @param int|string $scalar */
        public static function tryFrom($scalar): ?static;
    }
}
