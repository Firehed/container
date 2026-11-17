<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileOrEnvSource::class)]
#[CoversClass(Exceptions\ConflictingEnvironmentVariables::class)]
class FileOrEnvSourceTest extends TestCase
{
    private const NAME = 'CONTAINER_UNITTEST_SECRET';

    private string $path;

    public function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'container-fileorenv-');
        self::assertIsString($path, 'Temp file should be created');
        file_put_contents($path, "from-file\n");
        $this->path = $path;
    }

    public function tearDown(): void
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }

    public function testReadsEnvWhenFileVarNotSet(): void
    {
        $source = new FileOrEnvSource(self::NAME);
        $value = $source->read(new EnvReader([self::NAME => 'from-env']));
        self::assertSame('from-env', $value, 'Env value should be used when _FILE is not set');
    }

    public function testReadsFileWhenFileVarSet(): void
    {
        $source = new FileOrEnvSource(self::NAME);
        $value = $source->read(new EnvReader([self::NAME . '_FILE' => $this->path]));
        self::assertSame('from-file', $value, 'File contents should be used when _FILE is set');
    }

    public function testReturnsNullWhenNeitherSet(): void
    {
        $source = new FileOrEnvSource(self::NAME);
        self::assertNull($source->read(new EnvReader([])), 'Unset should defer to the default handling');
    }

    /** @param array<string, string> $env */
    #[DataProvider('bothSet')]
    public function testThrowsWhenBothSet(array $env): void
    {
        $source = new FileOrEnvSource(self::NAME);
        $this->expectException(Exceptions\ConflictingEnvironmentVariables::class);
        $source->read(new EnvReader($env));
    }

    /** @return array<string, array{array<string, string>}> */
    public static function bothSet(): array
    {
        return [
            'both non-empty' => [[self::NAME => 'x', self::NAME . '_FILE' => '/dev/null']],
            'empty env value' => [[self::NAME => '', self::NAME . '_FILE' => '/dev/null']],
        ];
    }

    #[DataProvider('unreadableFileVars')]
    public function testThrowsWhenFileUnreadable(string $fileVar): void
    {
        $source = new FileOrEnvSource(self::NAME);
        $this->expectException(Exceptions\FileNotReadable::class);
        $source->read(new EnvReader([self::NAME . '_FILE' => $fileVar]));
    }

    /** @return array<string, array{string}> */
    public static function unreadableFileVars(): array
    {
        return [
            'missing file' => ['/nonexistent/container-unittest'],
            'empty path' => [''],
        ];
    }

    public function testGetName(): void
    {
        self::assertSame(self::NAME, (new FileOrEnvSource(self::NAME))->getName(), 'Name should be the env var');
    }
}
