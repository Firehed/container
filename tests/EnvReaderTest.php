<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnvReader::class)]
#[Small]
class EnvReaderTest extends TestCase
{
    public function testReadFromEnvArray(): void
    {
        $reader = new EnvReader(['FOO' => 'bar']);
        self::assertSame('bar', $reader->read('FOO'));
    }

    public function testReadMissingKeyReturnsNull(): void
    {
        $reader = new EnvReader([]);
        self::assertNull($reader->read('NONEXISTENT'));
    }

    public function testEnvArrayTakesPrecedenceOverGetenv(): void
    {
        putenv('TEST_PRECEDENCE=from_getenv');
        $reader = new EnvReader(['TEST_PRECEDENCE' => 'from_array']);
        self::assertSame('from_array', $reader->read('TEST_PRECEDENCE'));
        putenv('TEST_PRECEDENCE'); // cleanup
    }

    public function testFallsBackToGetenv(): void
    {
        putenv('TEST_FALLBACK=from_getenv');
        $reader = new EnvReader([]);
        self::assertSame('from_getenv', $reader->read('TEST_FALLBACK'));
        putenv('TEST_FALLBACK'); // cleanup
    }
}
