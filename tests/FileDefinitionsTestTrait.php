<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

trait FileDefinitionsTestTrait
{
    /**
     * @param array<string, string> $env
     */
    #[DataProvider('fileValues')]
    public function testFileValue(string $key, array $env, mixed $expected): void
    {
        foreach ($env as $name => $value) {
            $_ENV[$name] = $value;
        }
        $container = $this->getContainer();
        self::assertTrue($container->has($key), 'has should be true');
        self::assertSame($expected, $container->get($key), 'get should return the value');
    }

    /**
     * @param array<string, string> $env
     * @param class-string<Throwable> $exception
     */
    #[DataProvider('fileErrors')]
    public function testFileError(string $key, array $env, string $exception): void
    {
        foreach ($env as $name => $value) {
            $_ENV[$name] = $value;
        }
        $container = $this->getContainer();
        self::assertTrue($container->has($key), 'has should be true');
        $this->expectException($exception);
        $container->get($key);
    }

    /** @return array<string, array{string, array<string, string>, mixed}> */
    public static function fileValues(): array
    {
        return [
            'fileContents' => ['file_contents', [], 'from-file'],
        ];
    }

    /** @return array<string, array{string, array<string, string>, class-string<Throwable>}> */
    public static function fileErrors(): array
    {
        return [
            'fileContents missing' => ['file_contents_missing', [], Exceptions\FileNotReadable::class],
        ];
    }
}
