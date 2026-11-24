<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

#[CoversFunction('Firehed\Container\fileContents')]
class FunctionsTest extends TestCase
{
    public function testFileContents(): void
    {
        $def = fileContents(__DIR__ . '/Fixtures/secret.txt');
        self::assertSame('from-file', $this->resolve($def), 'Definition should read the given path');
    }

    private function resolve(DefinitionInterface $def): mixed
    {
        return $def->resolve(
            self::createStub(TypedContainerInterface::class),
            self::createStub(EnvReader::class),
        );
    }
}
