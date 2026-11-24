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

    private const NEWLINE_FIXTURES = __DIR__ . '/Fixtures/newlines/';

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

    #[DataProvider('newlineFixtures')]
    public function testTrailingNewlinesAreStripped(string $fixture, string $expected): void
    {
        $path = self::NEWLINE_FIXTURES . $fixture;
        self::assertStringEndsWith("\n", $this->readFixture($path), 'Invalid fixture: must end with a newline');
        self::assertSame(
            $expected,
            $this->resolve(new FileContents($path)),
            'Only trailing newlines should be stripped',
        );
    }

    #[DataProvider('noNewlineFixtures')]
    public function testContentsWithoutTrailingNewlineAreUnchanged(string $fixture): void
    {
        $path = self::NEWLINE_FIXTURES . $fixture;
        $raw = $this->readFixture($path);
        self::assertStringEndsNotWith("\n", $raw, 'Invalid fixture: must not end with a newline');
        self::assertSame($raw, $this->resolve(new FileContents($path)), 'Contents should be returned as-is');
    }

    /** @return array<string, array{string, string}> */
    public static function newlineFixtures(): array
    {
        return [
            'LF' => ['lf.txt', 'secret'],
            'CRLF' => ['crlf.txt', 'secret'],
            'multiple' => ['multiple.txt', 'secret'],
            'other whitespace kept' => ['whitespace.txt', "  two\nlines \t"],
        ];
    }

    /** @return array<string, array{string}> */
    public static function noNewlineFixtures(): array
    {
        return [
            'plain' => ['none.txt'],
            'empty' => ['empty.txt'],
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

    private function readFixture(string $path): string
    {
        $raw = file_get_contents($path);
        self::assertIsString($raw, 'Fixture should be readable');
        return $raw;
    }

    private function resolve(FileContents $def): mixed
    {
        return $this->resolveDefinition($def, self::createStub(EnvReader::class));
    }
}
