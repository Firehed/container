<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileContents::class)]
#[CoversClass(Exceptions\FileNotReadable::class)]
class FileContentsTest extends TestCase
{
    use DefinitionTestTrait;

    private string $path;

    protected function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'container-test-');
        self::assertIsString($path, 'Temp file should be created');
        $this->path = $path;
    }

    protected function tearDown(): void
    {
        unlink($this->path);
    }

    public function testIsCacheable(): void
    {
        self::assertTrue(
            (new FileContents($this->path))->isCacheable(),
            'File contents should only be read once',
        );
    }

    public function testHasNoDependencies(): void
    {
        self::assertSame(
            [],
            (new FileContents($this->path))->getDependencies(),
            'Files depend on no other container entries',
        );
    }

    #[DataProvider('contents')]
    public function testResolve(string $contents, string $expected): void
    {
        file_put_contents($this->path, $contents);
        self::assertSame(
            $expected,
            $this->resolve(new FileContents($this->path)),
            'Only trailing newlines should be stripped',
        );
    }

    /** @return array<string, array{string, string}> */
    public static function contents(): array
    {
        return [
            'plain' => ['secret', 'secret'],
            'empty' => ['', ''],
            'trailing LF' => ["secret\n", 'secret'],
            'trailing CRLF' => ["secret\r\n", 'secret'],
            'multiple trailing newlines' => ["secret\n\n", 'secret'],
            'other whitespace kept' => ["  sec\nret \t\n", "  sec\nret \t"],
        ];
    }

    public function testMissingFileThrows(): void
    {
        $def = new FileContents($this->path . '-missing');
        $this->expectException(Exceptions\FileNotReadable::class);
        $this->resolve($def);
    }

    public function testDirectoryThrows(): void
    {
        $def = new FileContents(sys_get_temp_dir());
        $this->expectException(Exceptions\FileNotReadable::class);
        $this->resolve($def);
    }

    public function testGeneratedCodeDoesNotContainContents(): void
    {
        file_put_contents($this->path, 'super-secret-value');
        $code = (new FileContents($this->path))->generateCode();
        self::assertStringNotContainsString(
            'super-secret-value',
            $code,
            'Secrets must be read at runtime, not compiled in',
        );
    }

    public function testGeneratedCodeReadsFileWhenRun(): void
    {
        file_put_contents($this->path, "at-compile-time\n");
        $code = (new FileContents($this->path))->generateCode();
        file_put_contents($this->path, "at-runtime\n");
        self::assertSame(
            'at-runtime',
            $this->evaluateGeneratedCode($code, self::createStub(EnvReader::class)),
            'Compiled code should read the file when run, the same way as resolve()',
        );
    }

    private function resolve(FileContents $def): mixed
    {
        return $def->resolve(
            self::createStub(TypedContainerInterface::class),
            self::createStub(EnvReader::class),
        );
    }
}
