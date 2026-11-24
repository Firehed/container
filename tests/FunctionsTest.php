<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

#[CoversFunction('Firehed\Container\fileContents')]
#[CoversFunction('Firehed\Container\fileOrEnv')]
class FunctionsTest extends TestCase
{
    use DefinitionTestTrait;

    public function testFileContents(): void
    {
        $def = fileContents(__DIR__ . '/Fixtures/secret.txt');
        self::assertSame('from-file', $this->resolve($def), 'Definition should read the given path');
    }

    public function testFileOrEnvWithNullDefault(): void
    {
        $def = fileOrEnv('CONTAINER_UNITTEST_FUNCTIONS_NOT_SET', null);
        self::assertNull($this->resolve($def), 'An explicit null default should be used');
    }

    public function testFileOrEnvWithoutDefault(): void
    {
        $def = fileOrEnv('CONTAINER_UNITTEST_FUNCTIONS_NOT_SET');
        $this->expectException(Exceptions\EnvironmentVariableNotSet::class);
        $this->resolve($def);
    }

    private function resolve(DefinitionInterface $def): mixed
    {
        return $this->resolveDefinition($def, self::createStub(EnvReader::class));
    }
}
