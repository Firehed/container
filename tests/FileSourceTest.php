<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileSource::class)]
#[CoversClass(Exceptions\FileNotReadable::class)]
class FileSourceTest extends TestCase
{
    private string $path;

    public function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'container-filesource-');
        self::assertIsString($path, 'Temp file should be created');
        $this->path = $path;
    }

    public function tearDown(): void
    {
        if (file_exists($this->path)) {
            chmod($this->path, 0600);
            unlink($this->path);
        }
    }

    #[DataProvider('contents')]
    public function testReadReturnsContents(string $contents, string $expected): void
    {
        file_put_contents($this->path, $contents);
        $source = new FileSource($this->path);
        self::assertSame($expected, $source->read(new EnvReader([])), 'Trailing CR/LF should be stripped');
    }

    /** @return array<string, array{string, string}> */
    public static function contents(): array
    {
        return [
            'no newline' => ['secret', 'secret'],
            'trailing LF' => ["secret\n", 'secret'],
            'trailing CRLF' => ["secret\r\n", 'secret'],
            'multiple trailing newlines' => ["secret\n\r\n\n", 'secret'],
            'trailing space kept' => ["secret \n", 'secret '],
            'leading whitespace kept' => ["\n secret", "\n secret"],
            'inner newlines kept' => ["line1\nline2\n", "line1\nline2"],
            'empty' => ['', ''],
            'only newline' => ["\n", ''],
        ];
    }

    public function testReadThrowsWhenFileMissing(): void
    {
        unlink($this->path);
        $source = new FileSource($this->path);
        $this->expectException(Exceptions\FileNotReadable::class);
        $source->read(new EnvReader([]));
    }

    public function testReadThrowsOnDirectory(): void
    {
        $source = new FileSource(__DIR__);
        $this->expectException(Exceptions\FileNotReadable::class);
        $source->read(new EnvReader([]));
    }

    public function testReadThrowsOnEmptyPath(): void
    {
        $source = new FileSource('');
        $this->expectException(Exceptions\FileNotReadable::class);
        $source->read(new EnvReader([]));
    }

    public function testReadThrowsWhenNotReadable(): void
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            self::markTestSkipped('File permissions are not enforced for root');
        }
        file_put_contents($this->path, 'secret');
        chmod($this->path, 0000);
        $source = new FileSource($this->path);
        $this->expectException(Exceptions\FileNotReadable::class);
        $source->read(new EnvReader([]));
    }

    public function testExceptionNamesPath(): void
    {
        $source = new FileSource(__DIR__);
        try {
            $source->read(new EnvReader([]));
            self::fail('Expected exception not thrown');
        } catch (Exceptions\FileNotReadable $e) {
            self::assertStringContainsString(__DIR__, $e->getMessage(), 'Message should name the path');
        }
    }

    public function testGetName(): void
    {
        self::assertSame('/run/secrets/x', (new FileSource('/run/secrets/x'))->getName(), 'Name should be the path');
    }
}
