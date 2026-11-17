<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnvSource::class)]
class EnvSourceTest extends TestCase
{
    public function testReadReturnsEnvValue(): void
    {
        $source = new EnvSource('FOO');
        self::assertSame('bar', $source->read(new EnvReader(['FOO' => 'bar'])), 'Set value should be returned');
    }

    public function testReadReturnsNullWhenNotSet(): void
    {
        $source = new EnvSource('CONTAINER_UNITTEST_NOT_SET');
        self::assertNull($source->read(new EnvReader([])), 'Unset value should be null');
    }

    public function testGetName(): void
    {
        self::assertSame('FOO', (new EnvSource('FOO'))->getName(), 'Name is used in not-set errors');
    }

    public function testGenerateReadExpression(): void
    {
        self::assertSame(
            "\$this->envReader->read('FOO')",
            (new EnvSource('FOO'))->generateReadExpression(),
            'Compiled code should read the env at runtime',
        );
    }
}
