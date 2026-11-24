<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

#[CoversFunction('Firehed\Container\fileContents')]
class FunctionsTest extends TestCase
{
    use DefinitionTestTrait;

    public function testFileContents(): void
    {
        $def = fileContents(__DIR__ . '/Fixtures/fileContents/secret.txt');
        self::assertSame(
            'from-file',
            $this->resolveDefinition($def, self::createStub(EnvReader::class)),
            'Definition should read the given path',
        );
    }
}
