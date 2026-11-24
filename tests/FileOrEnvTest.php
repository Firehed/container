<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileOrEnv::class)]
#[CoversClass(Exceptions\ConflictingEnvironmentVariables::class)]
class FileOrEnvTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'container-test-');
        self::assertIsString($path, 'Temp file should be created');
        file_put_contents($path, "from-file\n");
        $this->path = $path;
    }

    protected function tearDown(): void
    {
        unlink($this->path);
    }

    public function testIsCacheable(): void
    {
        self::assertTrue((new FileOrEnv('FOO'))->isCacheable(), 'Value should only be read once');
    }

    public function testHasNoDependencies(): void
    {
        self::assertSame([], (new FileOrEnv('FOO'))->getDependencies(), 'No other container entries are needed');
    }

    public function testReadsEnvWhenOnlyEnvIsSet(): void
    {
        $value = $this->resolve(new FileOrEnv('FOO'), ['FOO' => 'from-env']);
        self::assertSame('from-env', $value, 'The env var should be used');
    }

    public function testReadsFileWhenOnlyFileIsSet(): void
    {
        $value = $this->resolve(new FileOrEnv('FOO'), ['FOO_FILE' => $this->path]);
        self::assertSame('from-file', $value, 'The file named by FOO_FILE should be read');
    }

    public function testThrowsWhenBothAreSet(): void
    {
        $this->expectException(Exceptions\ConflictingEnvironmentVariables::class);
        $this->resolve(new FileOrEnv('FOO'), ['FOO' => 'from-env', 'FOO_FILE' => $this->path]);
    }

    public function testThrowsWhenFileIsMissing(): void
    {
        $this->expectException(Exceptions\FileNotReadable::class);
        $this->resolve(new FileOrEnv('FOO'), ['FOO_FILE' => $this->path . '-missing']);
    }

    public function testThrowsWhenNeitherIsSetWithoutDefault(): void
    {
        $this->expectException(Exceptions\EnvironmentVariableNotSet::class);
        $this->resolve(new FileOrEnv('FOO'), []);
    }

    public function testReturnsDefaultWhenNeitherIsSet(): void
    {
        self::assertSame('fallback', $this->resolve(new FileOrEnv('FOO', 'fallback'), []), 'Default should apply');
    }

    public function testReturnsNullDefaultWhenNeitherIsSet(): void
    {
        self::assertNull($this->resolve(new FileOrEnv('FOO', null), []), 'Explicit null default should apply');
    }

    public function testResolveDoesNotCast(): void
    {
        $def = new FileOrEnv('FOO');
        $def->asInt();
        self::assertSame('42', $this->resolve($def, ['FOO' => '42']), 'Casting is applied by the container');
    }

    /**
     * @param array<string, string> $env
     */
    private function resolve(FileOrEnv $def, array $env): mixed
    {
        $reader = self::createStub(EnvReader::class);
        $reader->method('read')->willReturnCallback(fn (string $key): ?string => $env[$key] ?? null);
        return $def->resolve(self::createStub(TypedContainerInterface::class), $reader);
    }
}
