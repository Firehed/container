<?php

declare(strict_types=1);

namespace Firehed\Container\Compiler;

use PhpParser\{Parser, ParserFactory, PhpVersion};

/**
 * This is to support major versions 4 and 5 of nikic/php-parser, which allows
 * Container to be used in more projects.
 */
class ParserLoader
{
    public static function getParser(): Parser
    {
        $factory = new ParserFactory();
        if (method_exists($factory, 'createForVersion')) { // @phpstan-ignore-line
            // v5
            return $factory->createForVersion(PhpVersion::fromString('7.0'));
        } else {
            // v4
            assert(method_exists($factory, 'create'));
            assert(defined(ParserFactory::class . '::PREFER_PHP7'));
            return $factory->create(ParserFactory::PREFER_PHP7);
        }
    }
}
